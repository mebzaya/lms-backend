<?php

namespace App\Support;

/**
 * Auto-grading logic shared by every reading-style attempt flow (per-lesson
 * assessments and subject-wide exams both use multiple_choice/short_answer/
 * fill_in_blank questions graded the exact same way). Kept as a trait rather
 * than duplicated so the delicate Unicode/Nepali normalization and the
 * order-independent fill-in-blank matching only need to be right in one
 * place.
 */
trait GradesReadingAnswers
{
    protected function isAnswerCorrect(string $questionType, string $responseText, string $correctAnswer): bool
    {
        if ($questionType === 'multiple_choice' || $questionType === 'true_false') {
            return $responseText === $correctAnswer;
        }

        if ($questionType === 'fill_in_blank') {
            return $this->fillInBlankMatches($correctAnswer, $responseText);
        }

        // short_answer: a teacher may list spelling variants separated by
        // "|" (e.g. "colour|color"), same syntax as a fill-in-blank slot —
        // if they never bother listing any, this is still a one-element
        // list of the single answer they typed.
        return $this->anyVariantMatches(explode('|', $correctAnswer), $responseText);
    }

    /**
     * Canonical form for comparing typed answers, so text that is the same
     * to a reader compares equal whatever keyboard produced it:
     *  - Unicode NFC, so precomposed and decomposed Devanagari sequences
     *    (e.g. nukta forms) match.
     *  - Zero-width joiner/non-joiner (U+200D/U+200C) removed. Nepali IMEs
     *    insert them to control conjunct rendering (क्‍ष vs क्ष); they change
     *    appearance, not the word.
     *  - Trailing sentence punctuation (। . ! ?) dropped.
     *  - ALL whitespace removed, not just collapsed. A fill-in-blank/
     *    short-answer box is expected to hold a single word, so a stray
     *    space anywhere — leading, trailing, doubled, or typed by accident
     *    in the middle while correcting a typo, including the trailing
     *    space the phonetic Nepali converter leaves after converting a word
     *    on the boundary keystroke — should never turn a right answer wrong.
     *  - Lowercased, for English answers.
     */
    protected function normalizeAnswer(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        $text = str_replace(["\u{200C}", "\u{200D}"], '', $text);
        $text = preg_replace('/[।.!?]+$/u', '', trim($text));
        $text = preg_replace('/\s+/u', '', $text);

        return mb_strtolower($text);
    }

    /**
     * Whether a student's typed answer matches one accepted spelling —
     * case-insensitive (via normalizeAnswer) and tolerant of the student
     * writing the plain English singular/plural form the teacher didn't
     * think to list (e.g. "root" accepted, student writes "roots"). This is
     * a light heuristic (strip a trailing "s"/"es"/"ies"), not real
     * stemming — good enough for the common regular-plural case without
     * risking false positives on genuinely different words.
     */
    protected function answersMatch(string $given, string $accepted): bool
    {
        $given = $this->normalizeAnswer($given);
        $accepted = $this->normalizeAnswer($accepted);

        if ($given === $accepted) {
            return true;
        }

        return $this->stripTrailingPlural($given) === $this->stripTrailingPlural($accepted);
    }

    protected function stripTrailingPlural(string $text): string
    {
        if (mb_strlen($text) > 3 && str_ends_with($text, 'ies')) {
            return mb_substr($text, 0, -3).'y'; // berries -> berry
        }

        if (mb_strlen($text) > 2 && str_ends_with($text, 'es')) {
            return mb_substr($text, 0, -2); // boxes -> box
        }

        if (mb_strlen($text) > 1 && str_ends_with($text, 's')) {
            return mb_substr($text, 0, -1); // roots -> root
        }

        return $text;
    }

    /**
     * Whether a student's answer matches any one of a list of accepted
     * spelling variants.
     */
    protected function anyVariantMatches(array $acceptedVariants, string $given): bool
    {
        foreach ($acceptedVariants as $variant) {
            if ($this->answersMatch($given, $variant)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A question can have several blanks — each blank's accepted answer(s)
     * are stored joined by ";" (one segment per blank), and within a segment
     * a teacher may list spelling variants separated by "|" (e.g.
     * "colour|color"). The student isn't required to fill the blanks in the
     * same order the teacher listed them in, so this checks whether every
     * expected blank can be paired with a distinct given answer — a
     * bipartite matching — rather than comparing same-index pairs directly.
     */
    protected function fillInBlankMatches(string $correctAnswer, string $responseText): bool
    {
        $expectedSlots = explode(';', $correctAnswer);
        $givenSlots = explode(';', $responseText);

        if (count($expectedSlots) !== count($givenSlots)) {
            return false;
        }

        $acceptedSets = array_map(fn ($slot) => explode('|', $slot), $expectedSlots);

        $matchOfGiven = array_fill(0, count($givenSlots), null);

        foreach (array_keys($acceptedSets) as $slotIndex) {
            $visited = array_fill(0, count($givenSlots), false);

            if (! $this->tryMatchBlankSlot($slotIndex, $acceptedSets, $givenSlots, $matchOfGiven, $visited)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Standard Kuhn's-algorithm augmenting path: try to give this expected
     * slot one of the still-available given answers, bumping a
     * previously-assigned slot to a different given answer if needed.
     */
    protected function tryMatchBlankSlot(int $slotIndex, array $acceptedSets, array $given, array &$matchOfGiven, array &$visited): bool
    {
        foreach ($given as $givenIndex => $answer) {
            if ($visited[$givenIndex] || ! $this->anyVariantMatches($acceptedSets[$slotIndex], $answer)) {
                continue;
            }

            $visited[$givenIndex] = true;

            if ($matchOfGiven[$givenIndex] === null
                || $this->tryMatchBlankSlot($matchOfGiven[$givenIndex], $acceptedSets, $given, $matchOfGiven, $visited)
            ) {
                $matchOfGiven[$givenIndex] = $slotIndex;

                return true;
            }
        }

        return false;
    }
}
