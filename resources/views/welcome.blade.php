<?php $appName = config('app.name', 'LearnHub'); ?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $appName }} &mdash; Learning Management System</title>
        <meta name="description" content="{{ $appName }} is a modern learning management system for expert-led courses, progress tracking and certificates.">

        @fonts

        <style>
            :root {
                --lms-primary: #4f46e5;
                --lms-primary-dark: #4338ca;
                --lms-accent: #8b5cf6;
                --lms-ink: #1a1a2e;
                --lms-ink-soft: #5b5b76;
                --lms-border: #e6e4f0;
                --lms-bg-soft: #f7f7fc;
                --lms-radius: 16px;
                --lms-shadow: 0 20px 45px -15px rgba(79, 70, 229, 0.25);
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
                overflow-x: hidden;
            }

            body {
                margin: 0;
                overflow-x: hidden;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
                color: var(--lms-ink);
                background: #ffffff;
                -webkit-font-smoothing: antialiased;
                line-height: 1.55;
            }

            img, svg {
                display: block;
                max-width: 100%;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .lms-container {
                width: 100%;
                max-width: 1160px;
                margin-inline: auto;
                padding-inline: 24px;
            }

            /* Navbar */
            .lms-navbar {
                position: sticky;
                top: 0;
                z-index: 50;
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid var(--lms-border);
            }

            .lms-navbar-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding-block: 16px;
            }

            .lms-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 700;
                font-size: 1.25rem;
                color: var(--lms-ink);
            }

            .lms-brand-mark {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                background: linear-gradient(135deg, var(--lms-primary), var(--lms-accent));
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .lms-nav-links {
                display: flex;
                align-items: center;
                gap: 32px;
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .lms-nav-links a {
                font-size: 0.95rem;
                font-weight: 500;
                color: var(--lms-ink-soft);
                transition: color 0.15s ease;
            }

            .lms-nav-links a:hover {
                color: var(--lms-primary);
            }

            .lms-nav-actions {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            /* Buttons */
            .lms-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border-radius: 10px;
                padding: 11px 22px;
                font-size: 0.95rem;
                font-weight: 600;
                cursor: pointer;
                border: 1px solid transparent;
                transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
                white-space: nowrap;
            }

            .lms-btn-primary {
                background: linear-gradient(135deg, var(--lms-primary), var(--lms-accent));
                color: #fff;
                box-shadow: 0 10px 20px -8px rgba(79, 70, 229, 0.55);
            }

            .lms-btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 14px 24px -8px rgba(79, 70, 229, 0.6);
            }

            .lms-btn-ghost {
                background: transparent;
                color: var(--lms-ink);
                border-color: var(--lms-border);
            }

            .lms-btn-ghost:hover {
                border-color: var(--lms-primary);
                color: var(--lms-primary);
            }

            .lms-btn-white {
                background: #ffffff;
                color: var(--lms-primary-dark);
            }

            .lms-btn-white:hover {
                transform: translateY(-1px);
                box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.25);
            }

            .lms-btn-outline-light {
                background: transparent;
                color: #fff;
                border-color: rgba(255, 255, 255, 0.5);
            }

            .lms-btn-outline-light:hover {
                border-color: #fff;
                background: rgba(255, 255, 255, 0.08);
            }

            .lms-btn-block {
                width: 100%;
            }

            /* Hero */
            .lms-hero {
                position: relative;
                overflow: hidden;
                background: radial-gradient(circle at 15% 15%, #4338ca 0%, #312e81 45%, #1e1b4b 100%);
                padding-block: 72px 96px;
            }

            .lms-hero::before,
            .lms-hero::after {
                content: '';
                position: absolute;
                border-radius: 50%;
                filter: blur(10px);
                opacity: 0.35;
                pointer-events: none;
            }

            .lms-hero::before {
                width: 420px;
                height: 420px;
                background: #8b5cf6;
                top: -160px;
                right: -120px;
            }

            .lms-hero::after {
                width: 320px;
                height: 320px;
                background: #22d3ee;
                bottom: -140px;
                left: -100px;
                opacity: 0.25;
            }

            .lms-hero-grid {
                position: relative;
                z-index: 1;
                display: grid;
                grid-template-columns: 1.1fr 0.9fr;
                gap: 56px;
                align-items: center;
            }

            .lms-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 16px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.18);
                color: #e0e7ff;
                font-size: 0.82rem;
                font-weight: 600;
                letter-spacing: 0.02em;
                margin-bottom: 24px;
            }

            .lms-hero h1 {
                font-size: clamp(2.2rem, 4vw, 3.4rem);
                line-height: 1.12;
                font-weight: 700;
                color: #ffffff;
                margin: 0 0 20px;
                letter-spacing: -0.02em;
            }

            .lms-hero h1 span {
                background: linear-gradient(135deg, #a5b4fc, #f0abfc);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .lms-hero p.lms-lead {
                font-size: 1.1rem;
                color: #c7d2fe;
                max-width: 520px;
                margin: 0 0 32px;
            }

            .lms-hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                margin-bottom: 44px;
            }

            .lms-stats {
                display: flex;
                gap: 36px;
                flex-wrap: wrap;
            }

            .lms-stat b {
                display: block;
                font-size: 1.6rem;
                font-weight: 700;
                color: #ffffff;
            }

            .lms-stat span {
                font-size: 0.85rem;
                color: #a5b4fc;
            }

            /* Login card */
            .lms-login-card {
                position: relative;
                background: #ffffff;
                border-radius: 20px;
                padding: 36px 32px;
                box-shadow: var(--lms-shadow);
                width: 100%;
                max-width: 420px;
                margin-inline: auto;
            }

            .lms-login-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--lms-primary), var(--lms-accent));
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 18px;
            }

            .lms-login-card h2 {
                font-size: 1.4rem;
                margin: 0 0 6px;
                font-weight: 700;
            }

            .lms-login-card > p {
                color: var(--lms-ink-soft);
                font-size: 0.92rem;
                margin: 0 0 24px;
            }

            .lms-field {
                margin-bottom: 16px;
            }

            .lms-field label {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                margin-bottom: 6px;
                color: var(--lms-ink);
            }

            .lms-field input {
                width: 100%;
                padding: 11px 14px;
                border-radius: 10px;
                border: 1px solid var(--lms-border);
                font-size: 0.95rem;
                font-family: inherit;
                background: var(--lms-bg-soft);
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .lms-field input:focus {
                outline: none;
                border-color: var(--lms-primary);
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
                background: #ffffff;
            }

            .lms-field-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 22px;
                font-size: 0.85rem;
            }

            .lms-checkbox {
                display: flex;
                align-items: center;
                gap: 8px;
                color: var(--lms-ink-soft);
            }

            .lms-checkbox input {
                width: 16px;
                height: 16px;
                accent-color: var(--lms-primary);
            }

            .lms-field-row a {
                color: var(--lms-primary);
                font-weight: 600;
            }

            .lms-divider {
                display: flex;
                align-items: center;
                gap: 12px;
                margin: 22px 0;
                color: var(--lms-ink-soft);
                font-size: 0.8rem;
            }

            .lms-divider::before,
            .lms-divider::after {
                content: '';
                flex: 1;
                height: 1px;
                background: var(--lms-border);
            }

            .lms-signup-note {
                text-align: center;
                font-size: 0.88rem;
                color: var(--lms-ink-soft);
            }

            .lms-signup-note a {
                color: var(--lms-primary);
                font-weight: 700;
            }

            .lms-welcome-avatar {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--lms-primary), var(--lms-accent));
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.4rem;
                font-weight: 700;
                margin: 0 auto 18px;
            }

            /* Sections */
            .lms-section {
                padding-block: 88px;
            }

            .lms-section-head {
                text-align: center;
                max-width: 620px;
                margin: 0 auto 56px;
            }

            .lms-eyebrow-light {
                display: inline-block;
                color: var(--lms-primary);
                font-weight: 700;
                font-size: 0.82rem;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                margin-bottom: 12px;
            }

            .lms-section-head h2 {
                font-size: clamp(1.7rem, 3vw, 2.3rem);
                margin: 0 0 14px;
                font-weight: 700;
                letter-spacing: -0.01em;
            }

            .lms-section-head p {
                color: var(--lms-ink-soft);
                font-size: 1.05rem;
                margin: 0;
            }

            .lms-feature-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 28px;
            }

            .lms-feature-card {
                padding: 30px 26px;
                border-radius: var(--lms-radius);
                border: 1px solid var(--lms-border);
                background: #ffffff;
                transition: box-shadow 0.2s ease, transform 0.2s ease;
            }

            .lms-feature-card:hover {
                box-shadow: 0 20px 35px -18px rgba(31, 27, 77, 0.25);
                transform: translateY(-3px);
            }

            .lms-feature-icon {
                width: 46px;
                height: 46px;
                border-radius: 12px;
                background: #eef0ff;
                color: var(--lms-primary);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 18px;
            }

            .lms-feature-card h3 {
                font-size: 1.08rem;
                margin: 0 0 8px;
                font-weight: 700;
            }

            .lms-feature-card p {
                font-size: 0.92rem;
                color: var(--lms-ink-soft);
                margin: 0;
            }

            /* CTA band */
            .lms-cta {
                background: linear-gradient(135deg, #4338ca, #6d28d9);
                border-radius: 24px;
                padding: 56px 48px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                flex-wrap: wrap;
                color: #fff;
            }

            .lms-cta h2 {
                font-size: 1.6rem;
                margin: 0 0 8px;
                font-weight: 700;
            }

            .lms-cta p {
                margin: 0;
                color: #ddd6fe;
                max-width: 460px;
            }

            /* Footer */
            .lms-footer {
                background: #14122b;
                color: #b6b4d1;
                padding-block: 64px 32px;
            }

            .lms-footer-grid {
                display: grid;
                grid-template-columns: 1.4fr 1fr 1fr 1fr;
                gap: 40px;
                padding-bottom: 40px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .lms-footer .lms-brand {
                color: #fff;
            }

            .lms-footer-about p {
                margin: 14px 0 0;
                font-size: 0.92rem;
                max-width: 300px;
            }

            .lms-footer-col h4 {
                color: #fff;
                font-size: 0.92rem;
                margin: 0 0 16px;
            }

            .lms-footer-col ul {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .lms-footer-col a {
                font-size: 0.9rem;
                color: #b6b4d1;
                transition: color 0.15s ease;
            }

            .lms-footer-col a:hover {
                color: #fff;
            }

            .lms-footer-bottom {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                padding-top: 24px;
                font-size: 0.85rem;
            }

            .lms-social {
                display: flex;
                gap: 12px;
            }

            .lms-social a {
                width: 34px;
                height: 34px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.06);
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.15s ease;
            }

            .lms-social a:hover {
                background: rgba(255, 255, 255, 0.14);
            }

            @media (max-width: 960px) {
                .lms-hero-grid {
                    grid-template-columns: 1fr;
                }

                .lms-feature-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .lms-footer-grid {
                    grid-template-columns: 1fr 1fr;
                }

                .lms-nav-links {
                    display: none;
                }
            }

            @media (max-width: 640px) {
                .lms-feature-grid {
                    grid-template-columns: 1fr;
                }

                .lms-footer-grid {
                    grid-template-columns: 1fr;
                }

                .lms-cta {
                    flex-direction: column;
                    text-align: center;
                    padding: 40px 28px;
                }

                .lms-hero {
                    padding-block: 48px 64px;
                }
            }

            @media (max-width: 480px) {
                .lms-container {
                    padding-inline: 16px;
                }

                .lms-navbar-inner {
                    padding-block: 12px;
                }

                .lms-brand {
                    font-size: 1.05rem;
                    gap: 8px;
                }

                .lms-brand-mark {
                    width: 30px;
                    height: 30px;
                }

                .lms-nav-actions {
                    gap: 8px;
                }

                .lms-nav-actions .lms-btn {
                    padding: 8px 12px;
                    font-size: 0.82rem;
                }

                .lms-hero-actions .lms-btn,
                .lms-cta .lms-btn {
                    width: 100%;
                }

                .lms-login-card {
                    padding: 28px 20px;
                }
            }
        </style>
    </head>
    <body>

        <header class="lms-navbar">
            <div class="lms-container lms-navbar-inner">
                <a href="/" class="lms-brand">
                    <span class="lms-brand-mark">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3L1 8.5L12 14L21 9.6V16.5H23V8.5L12 3Z" fill="#fff"/>
                            <path d="M5 12.18V16.5C5 16.5 6.5 19.5 12 19.5C17.5 19.5 19 16.5 19 16.5V12.18L12 15.68L5 12.18Z" fill="#fff" fill-opacity="0.85"/>
                        </svg>
                    </span>
                    {{ $appName }}
                </a>

                <ul class="lms-nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#courses">Courses</a></li>
                    <li><a href="#about">About</a></li>
                </ul>

                <div class="lms-nav-actions">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="lms-btn lms-btn-primary">Go to Dashboard</a>
                    @else
                        <a href="#login-box" class="lms-btn lms-btn-ghost">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="lms-btn lms-btn-primary">Get Started</a>
                        @else
                            <a href="#login-box" class="lms-btn lms-btn-primary">Get Started</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <section class="lms-hero">
            <div class="lms-container lms-hero-grid">
                <div>
                    <span class="lms-eyebrow">🎓 The modern way to learn</span>
                    <h1>Learn new skills, <span>anytime, anywhere.</span></h1>
                    <p class="lms-lead">
                        {{ $appName }} brings expert-led courses, interactive quizzes and real progress
                        tracking into one place &mdash; so you can build real skills at your own pace.
                    </p>

                    <div class="lms-hero-actions">
                        <a href="#login-box" class="lms-btn lms-btn-white">Start Learning Free</a>
                        <a href="#features" class="lms-btn lms-btn-outline-light">Explore Features</a>
                    </div>

                    <div class="lms-stats">
                        <div class="lms-stat">
                            <b>12,000+</b>
                            <span>Active Students</span>
                        </div>
                        <div class="lms-stat">
                            <b>350+</b>
                            <span>Courses</span>
                        </div>
                        <div class="lms-stat">
                            <b>98%</b>
                            <span>Satisfaction Rate</span>
                        </div>
                    </div>
                </div>

                <div id="login-box" class="lms-login-card">
                    @auth
                        <div style="text-align:center">
                            <div class="lms-welcome-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</div>
                            <h2>Welcome back, {{ explode(' ', Auth::user()->name ?? 'there')[0] }}!</h2>
                            <p>Ready to pick up where you left off?</p>
                            <a href="{{ url('/dashboard') }}" class="lms-btn lms-btn-primary lms-btn-block">Continue Learning</a>
                        </div>
                    @else
                        <div class="lms-login-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12C14.2091 12 16 10.2091 16 8C16 5.79086 14.2091 4 12 4C9.79086 4 8 5.79086 8 8C8 10.2091 9.79086 12 12 12Z" stroke="#fff" stroke-width="1.6"/>
                                <path d="M4 20C4 16.6863 7.58172 14 12 14C16.4183 14 20 16.6863 20 20" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h2>Welcome back</h2>
                        <p>Sign in to continue your learning journey.</p>

                        <form method="POST" action="{{ Route::has('login') ? route('login') : url('/login') }}">
                            @csrf
                            <div class="lms-field">
                                <label for="email">Email address</label>
                                <input id="email" type="email" name="email" placeholder="you@example.com" required autofocus>
                            </div>

                            <div class="lms-field">
                                <label for="password">Password</label>
                                <input id="password" type="password" name="password" placeholder="••••••••" required>
                            </div>

                            <div class="lms-field-row">
                                <label class="lms-checkbox">
                                    <input type="checkbox" name="remember">
                                    Remember me
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">Forgot password?</a>
                                @endif
                            </div>

                            <button type="submit" class="lms-btn lms-btn-primary lms-btn-block">Log In</button>
                        </form>

                        <div class="lms-divider">OR</div>

                        <p class="lms-signup-note">
                            Don't have an account?
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}">Create one for free</a>
                            @else
                                <a href="#login-box">Create one for free</a>
                            @endif
                        </p>
                    @endauth
                </div>
            </div>
        </section>

        <section id="features" class="lms-section">
            <div class="lms-container">
                <div class="lms-section-head">
                    <span class="lms-eyebrow-light">Why {{ $appName }}</span>
                    <h2>Everything you need to succeed</h2>
                    <p>A complete learning toolkit for students, instructors and teams &mdash; all in one platform.</p>
                </div>

                <div class="lms-feature-grid">
                    <div class="lms-feature-card">
                        <div class="lms-feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3L1 8.5L12 14L23 8.5L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M5 12.18V16.5C5 16.5 8 19 12 19C16 19 19 16.5 19 16.5V12.18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h3>Expert-Led Courses</h3>
                        <p>Learn from industry professionals with structured, easy-to-follow lessons.</p>
                    </div>

                    <div class="lms-feature-card">
                        <div class="lms-feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </div>
                        <h3>Interactive Quizzes</h3>
                        <p>Reinforce every lesson with quizzes and assignments that check real understanding.</p>
                    </div>

                    <div class="lms-feature-card">
                        <div class="lms-feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3V21H21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 15L11 11L14 14L20 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h3>Progress Tracking</h3>
                        <p>Visual dashboards keep you motivated by showing exactly how far you've come.</p>
                    </div>

                    <div class="lms-feature-card">
                        <div class="lms-feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="5" stroke="currentColor" stroke-width="1.6"/><path d="M8.5 12.5L7 21L12 18L17 21L15.5 12.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h3>Certificates</h3>
                        <p>Earn shareable certificates of completion to showcase your new skills.</p>
                    </div>

                    <div class="lms-feature-card">
                        <div class="lms-feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="2" width="14" height="20" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M11 18H13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </div>
                        <h3>Learn on Any Device</h3>
                        <p>Pick up your courses seamlessly on desktop, tablet or mobile.</p>
                    </div>

                    <div class="lms-feature-card">
                        <div class="lms-feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 20H22V18C22 16.3431 20.6569 15 19 15C18.0444 15 17.1931 15.4468 16.6438 16.1429" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 20H17V18C17 15.7909 14.3137 14 11 14C7.68629 14 5 15.7909 5 18V20H2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M11 11C12.6569 11 14 9.65685 14 8C14 6.34315 12.6569 5 11 5C9.34315 5 8 6.34315 8 8C8 9.65685 9.34315 11 11 11Z" stroke="currentColor" stroke-width="1.6"/></svg>
                        </div>
                        <h3>Community Support</h3>
                        <p>Get help fast from instructors and fellow learners in course discussions.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="lms-section" style="padding-top:0;">
            <div class="lms-container">
                <div class="lms-cta">
                    <div>
                        <h2>Ready to start learning?</h2>
                        <p>Create your free account today and join thousands of learners already growing their skills on {{ $appName }}.</p>
                    </div>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="lms-btn lms-btn-white">Create Free Account</a>
                    @else
                        <a href="#login-box" class="lms-btn lms-btn-white">Create Free Account</a>
                    @endif
                </div>
            </div>
        </section>

        <footer class="lms-footer">
            <div class="lms-container">
                <div class="lms-footer-grid">
                    <div class="lms-footer-about">
                        <a href="/" class="lms-brand">
                            <span class="lms-brand-mark">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3L1 8.5L12 14L21 9.6V16.5H23V8.5L12 3Z" fill="#fff"/>
                                    <path d="M5 12.18V16.5C5 16.5 6.5 19.5 12 19.5C17.5 19.5 19 16.5 19 16.5V12.18L12 15.68L5 12.18Z" fill="#fff" fill-opacity="0.85"/>
                                </svg>
                            </span>
                            {{ $appName }}
                        </a>
                        <p>A modern learning management system helping students and instructors teach, learn and grow together.</p>
                        <div class="lms-social" style="margin-top:20px;">
                            <a href="#" aria-label="Twitter"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22 5.9C21.3 6.2 20.5 6.4 19.7 6.5C20.5 6 21.2 5.2 21.5 4.3C20.7 4.8 19.9 5.1 19 5.3C18.3 4.5 17.3 4 16.2 4C14.1 4 12.4 5.7 12.4 7.8C12.4 8.1 12.4 8.4 12.5 8.6C9.3 8.5 6.5 6.9 4.6 4.6C4.3 5.2 4.1 5.8 4.1 6.6C4.1 7.9 4.8 9.1 5.8 9.8C5.2 9.8 4.6 9.6 4.1 9.3V9.4C4.1 11.2 5.4 12.7 7.1 13.1C6.8 13.2 6.4 13.2 6.1 13.2C5.9 13.2 5.6 13.2 5.4 13.1C5.9 14.6 7.3 15.7 9 15.8C7.7 16.8 6.1 17.4 4.3 17.4C4 17.4 3.7 17.4 3.4 17.3C5.1 18.4 7.1 19 9.2 19C16.2 19 20 13.3 20 8.3V7.8C20.8 7.3 21.5 6.6 22 5.9Z" fill="currentColor"/></svg></a>
                            <a href="#" aria-label="LinkedIn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6.94 5.5C6.94 6.6 6.05 7.5 4.94 7.5C3.83 7.5 2.94 6.6 2.94 5.5C2.94 4.4 3.83 3.5 4.94 3.5C6.05 3.5 6.94 4.4 6.94 5.5Z" fill="currentColor"/><path d="M3.5 9H6.5V20.5H3.5V9Z" fill="currentColor"/><path d="M10 9H13V10.5C13.5 9.6 14.7 8.7 16.3 8.7C19 8.7 20.5 10.4 20.5 13.4V20.5H17.5V14.1C17.5 12.6 17 11.6 15.6 11.6C14.5 11.6 13.9 12.4 13.6 13.1C13.5 13.4 13.5 13.7 13.5 14.1V20.5H10.5C10.5 20.5 10.5 9.7 10 9Z" fill="currentColor"/></svg></a>
                            <a href="#" aria-label="GitHub"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.5 2 2 6.5 2 12C2 16.4 4.9 20.2 8.8 21.5C9.3 21.6 9.5 21.3 9.5 21V19.1C6.7 19.7 6.1 17.8 6.1 17.8C5.6 16.6 4.9 16.3 4.9 16.3C3.9 15.6 5 15.6 5 15.6C6.1 15.7 6.7 16.7 6.7 16.7C7.7 18.4 9.3 17.9 9.9 17.6C10 16.9 10.3 16.4 10.6 16.1C8.4 15.9 6 15 6 11.2C6 10.1 6.4 9.2 7 8.5C6.9 8.2 6.5 7.1 7.1 5.7C7.1 5.7 8 5.4 9.9 6.7C10.7 6.5 11.6 6.4 12.4 6.4C13.2 6.4 14.1 6.5 14.9 6.7C16.8 5.4 17.7 5.7 17.7 5.7C18.3 7.1 17.9 8.2 17.8 8.5C18.5 9.2 18.8 10.1 18.8 11.2C18.8 15 16.4 15.9 14.2 16.1C14.6 16.4 14.9 17.1 14.9 18.1V21C14.9 21.3 15.1 21.7 15.7 21.5C19.6 20.2 22.4 16.4 22.4 12C22.4 6.5 17.9 2 12.4 2H12Z" fill="currentColor"/></svg></a>
                        </div>
                    </div>

                    <div class="lms-footer-col">
                        <h4>Product</h4>
                        <ul>
                            <li><a href="#features">Features</a></li>
                            <li><a href="#courses">Courses</a></li>
                            <li><a href="#login-box">Log in</a></li>
                        </ul>
                    </div>

                    <div class="lms-footer-col">
                        <h4>Company</h4>
                        <ul>
                            <li><a href="#about">About</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>

                    <div class="lms-footer-col">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>

                <div class="lms-footer-bottom">
                    <span>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</span>
                    <span>Built with Laravel v{{ app()->version() }}</span>
                </div>
            </div>
        </footer>
    </body>
</html>
