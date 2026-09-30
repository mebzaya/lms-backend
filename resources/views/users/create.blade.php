<!DOCTYPE html>
<html>
<head><title>Create User</title>
</head>
<body>
    <h1>Create New User</h1>
    <form action="/users" method="POST">
        @csrf
        <label>Name:</label>
        <input type="text" name="name" required>

        <br><br>

        <label>Email:</label>
        <input type="email" name="email" required>

        <br><br>

        <label>Password:</label>
        <input type="password" name="password" required>

        <br><br>

        <button type="submit">Create User</button>
    </form>

    <br>

    <a href="/users">Back to Users</a>

</body>
</html>