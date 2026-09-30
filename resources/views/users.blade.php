<!DOCTYPE html>
<html>
<head>
    <title>Users</title>
</head>

<body>

    <h1>Users Management</h1>

    <h2>Create New User</h2>

    <form action="/users" method="POST">

        @csrf

        <label>Name:</label>
        <input type="text" name="name">

        <br><br>

        <label>Email:</label>
        <input type="email" name="email">

        <br><br>

        <label>Password:</label>
        <input type="password" name="password">

        <br><br>

        <button type="submit">Create User</button>

    </form>

    <hr>

    <h2>Users List</h2>

    <table border="1" cellpadding="10">

        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Action</th>
        </tr>

        @foreach ($users as $user)

        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>

            <td>
                 <a href="/users/{{ $user->id }}/edit">
                      Edit /
                 </a>

                <form action="/users/{{ $user->id }}" method="POST" style="display:inline;">

                    @csrf
                    @method('DELETE')

                    <button type="submit">Delete</button>

                </form>
            </td>
        </tr>

        @endforeach

    </table>

</body>
</html>
