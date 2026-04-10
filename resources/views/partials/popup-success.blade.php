<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Success</title>
</head>
<body>
    <script>
        // Notify parent window that form submission was successful
        window.parent.postMessage({ type: 'LIFECYCLE_SUCCESS', message: '{{ $message ?? "Action completed successfully" }}' }, '*');
    </script>
</body>
</html>
