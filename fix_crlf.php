<?php
$files = [
    'app/Http/Controllers/Auth/ResetPasswordController.php',
    'app/Http/Controllers/ProfileController.php',
    'app/Http/Controllers/SlotLifecycleController.php',
    'app/Http/Requests/UserStoreRequest.php',
    'app/Http/Requests/UserUpdateRequest.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace("\r\n", "\n", $content);
        file_put_contents($file, $content);
        echo "Converted $file\n";
    }
}
