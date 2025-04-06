<!-- resources/views/set_url.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Set Flask Public URL</title>
</head>
<body>
    <h1>Enter Flask Public URL</h1>
    <form action="{{ route('store.public.url') }}" method="POST">
        @csrf
        <input type="url" name="public_url" placeholder="https://your-ngrok-url.ngrok.io" required>
        <button type="submit">Save URL</button>
    </form>

    @if(session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif
</body>
</html>
