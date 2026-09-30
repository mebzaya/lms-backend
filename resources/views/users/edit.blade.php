<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
</head>

<body>

    <h1>Edit User</h1>

    <form action="/users/{{ $user->id }}" method="POST">

        @csrf
        @method('PUT')

        <label>Name:</label>
        <input type="text" name="name" value="{{ $user->name }}" required>

        <br><br>

        <label>Email:</label>
        <input type="email" name="email" value="{{ $user->email }}" required>

        <br><br>

        <button type="submit">Update User</button>

    </form>

    <br>

    <a href="/users">Back to Users</a>

</body>
</html>