<?php

// run after adding any function composer dump-autoload

// // Using class-based helpers
// $truncated = StringHelper::truncate('Long string here', 10);
// $percentage = MathHelper::percentage(25, 100);

// // Using global helpers
// $formattedPrice = format_price(19.99);
// $isAdmin = is_admin();

/*

// Date and Time Helpers
================================================================
   $currentDate = now();
   $formattedDate = format_date($currentDate, 'F d, Y');
   $timeAgo = time_ago($currentDate->subDays(3));
   $daysUntil = days_until('2024-12-31');
   $isWeekend = is_weekend();
   $userAge = age_from_date('1990-05-15');

   // String Manipulation Helpers
   $slug = generate_slug('Hello World, This is a Test!');
   $truncatedText = truncate_text('This is a very long text that needs to be shortened', 20);
   $maskedEmail = mask_email('johndoe@example.com');

   // Number and Money Helpers
   $price = money(1234.56);
   $formattedNumber = format_number(1234567.89, 2, '.', ',');
   $randomColor = random_color();

   // File and Size Helpers
   $fileSize = human_filesize(1048576); // 1 MB

   // Security and Generation Helpers
   $password = generate_password(16);
   $referenceNumber = generate_reference_number();

   // Environment and Input Helpers
   $isProduction = is_production();
   $sanitizedInput = sanitize_input($request->input('user_input', ''));

   // Phone and Validation
   $phoneValidation = validate_phone('+1234567890');

   // File Upload Validation (if a file is uploaded)
   $fileValidation = null;
   if ($request->hasFile('document')) {
      $fileValidation = validate_file_size($request->file('document'), 5, 1);
   }

   // Gravatar Generation
   $gravatarUrl = get_gravatar('user@example.com', 200);

   // Multiple Files Validation
   $multipleFileValidation = null;
   if ($request->hasFile('documents')) {
      $multipleFileValidation = validate_multiple_file_sizes($request->file('documents'), 10, 1);
   }

   // Generate CSV from an array
   $csvData = array_to_csv([
      ['Name', 'Email', 'Age'],
      ['John Doe', 'john@example.com', 30],
      ['Jane Smith', 'jane@example.com', 25]
   ]);
*/

if (! function_exists('format_response')) {

    /**
     * // Basic success response
     * return format_response('User created', $user);
     *
     * // Error response
     * return format_response('Validation failed', null, 422);
     *
     * // Response with additional metadata
     * return format_response('Custom data', $data, 200, ['additional_info' => 'Some extra context']);
     */

    /**
     * Format a standardized JSON response
     *
     * @param  string  $message  Response message
     * @param  mixed  $data  Optional data to be returned
     * @param  int  $status  HTTP status code
     * @param  array  $meta  Optional additional metadata
     */
    function format_response(
        string $message,
        $data = null,
        int $status = 200,
        array $meta = []
    ): \Illuminate\Http\JsonResponse {
        // Ensure we have a timestamp constant
        defined('LARAVEL_START') || define('LARAVEL_START', microtime(true));

        // Prepare the response structure
        $response = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'epoch' => now()->timestamp,
                'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                'server' => request()->server('SERVER_NAME'),
            ], $meta),
        ];

        // Return JSON response
        return response()->json($response, $status);
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date with optional custom format
     *
     * @param  mixed  $date
     */
    function format_date($date, string $format = 'M d, Y'): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return '';
        }
    }
}

if (! function_exists('generate_slug')) {
    /**
     * Generate a URL-friendly slug from a string
     */
    function generate_slug(string $string, string $separator = '-'): string
    {
        return \Illuminate\Support\Str::slug($string, $separator);
    }
}

if (! function_exists('truncate_text')) {
    /**
     * Truncate text to a specified length
     */
    function truncate_text(string $text, int $length = 100, string $end = '...'): string
    {
        return \Illuminate\Support\Str::limit($text, $length, $end);
    }
}

if (! function_exists('format_number')) {
    /**
     * Format a number with specified decimals and thousand separator
     */
    function format_number(float $number, int $decimals = 2, string $decPoint = '.', string $thousandsSep = ','): string
    {
        return number_format($number, $decimals, $decPoint, $thousandsSep);
    }
}

if (! function_exists('is_production')) {
    /**
     * Check if the application is running in production
     */
    function is_production(): bool
    {
        return app()->environment('production');
    }
}

if (! function_exists('get_gravatar')) {
    /**
     * Get Gravatar URL for an email address
     */
    function get_gravatar(string $email, int $size = 80, string $default = 'mp'): string
    {
        $email = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/{$email}?s={$size}&d={$default}";
    }
}

if (! function_exists('random_color')) {
    /**
     * Generate a random hex color
     */
    function random_color(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
}

if (! function_exists('days_until')) {
    /**
     * Calculate days until a given date
     *
     * @param  string|Carbon\Carbon  $date
     */
    function days_until($date): int
    {
        try {
            $carbon = \Carbon\Carbon::parse($date);

            return max(0, $carbon->diffInDays());
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (! function_exists('sanitize_input')) {
    /**
     * Sanitize input string
     */
    function sanitize_input(string $input): string
    {
        return strip_tags(trim($input));
    }
}

if (! function_exists('generate_unique_filename')) {
    /**
     * Generate a unique filename
     */
    function generate_unique_filename(string $originalName, string $path = ''): string
    {
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = $filename;
        $counter = 1;

        while (file_exists(($path ? $path.'/' : '').$uniqueName.'.'.$extension)) {
            $uniqueName = $filename.'_'.$counter;
            $counter++;
        }

        return $uniqueName.'.'.$extension;
    }
}

if (! function_exists('money')) {
    /**
     * Format money with currency
     */
    function money(float $amount, string $currency = '$', int $decimals = 2): string
    {
        return sprintf('%s%s', $currency, number_format($amount, $decimals, '.', ','));
    }
}

if (! function_exists('human_filesize')) {
    /**
     * Convert bytes to human-readable file size
     */
    function human_filesize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}

if (! function_exists('mask_email')) {
    /**
     * Mask email address
     */
    function mask_email(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        $masked_username = strlen($username) > 3
            ? substr($username, 0, 2).str_repeat('*', max(0, strlen($username) - 2))
            : $username;

        return $masked_username.'@'.$domain;
    }
}

if (! function_exists('age_from_date')) {
    /**
     * Calculate age from a given date
     *
     * @param  string|Carbon\Carbon  $date
     */
    function age_from_date($date): int
    {
        try {
            return \Carbon\Carbon::parse($date)->age;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (! function_exists('is_weekend')) {
    /**
     * Check if a given date is a weekend
     *
     * @param  string|Carbon\Carbon|null  $date
     */
    function is_weekend($date = null): bool
    {
        $carbon = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::now();

        return $carbon->isWeekend();
    }
}

if (! function_exists('generate_password')) {
    /**
     * Generate a secure random password
     */
    function generate_password(int $length = 12, bool $special_chars = true): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        if ($special_chars) {
            $chars .= $special;
        }

        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }
}

if (! function_exists('array_to_csv')) {
    /**
     * Convert array to CSV string
     */
    function array_to_csv(array $array, string $delimiter = ',', string $enclosure = '"'): string
    {
        $output = [];
        foreach ($array as $line) {
            $line_array = [];
            foreach ($line as $value) {
                $line_array[] = $enclosure.str_replace($enclosure, $enclosure.$enclosure, $value).$enclosure;
            }
            $output[] = implode($delimiter, $line_array);
        }

        return implode("\n", $output);
    }
}

if (! function_exists('time_ago')) {
    /**
     * Generate human-readable time difference
     *
     * @param  string|Carbon\Carbon  $date
     */
    function time_ago($date): string
    {
        try {
            return \Carbon\Carbon::parse($date)->diffForHumans();
        } catch (\Exception $e) {
            return '';
        }
    }
}

if (! function_exists('validate_phone')) {
    /**
     * Validate phone number
     */
    function validate_phone(string $phone): bool
    {
        // Basic phone number validation (adjust regex as needed)
        return preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $phone) === 1;
    }
}

if (! function_exists('generate_reference_number')) {
    /**
     * Generate a unique reference number
     */
    function generate_reference_number(int $length = 8): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $reference = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $reference .= $characters[random_int(0, $max)];
        }

        return $reference;
    }
}

if (! function_exists('validate_file_size')) {
    /**
     * Validate file size in a request
     *
     * @param  int  $maxSizeInMB  Maximum file size in megabytes
     * @param  int  $minSizeInKB  Minimum file size in kilobytes (optional)
     */
    function validate_file_size(\Illuminate\Http\UploadedFile $file, int $maxSizeInMB = 5, int $minSizeInKB = 1): array
    {
        // Convert sizes to bytes
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024;
        $minSizeInBytes = $minSizeInKB * 1024;

        // Get actual file size
        $fileSize = $file->getSize();

        // Validation checks
        $errors = [];

        // Check if file exists
        if (! $file->isValid()) {
            $errors[] = 'Invalid file upload.';
        }

        // Check minimum file size
        if ($fileSize < $minSizeInBytes) {
            $errors[] = sprintf(
                'File is too small. Minimum file size is %d KB.',
                $minSizeInKB
            );
        }

        // Check maximum file size
        if ($fileSize > $maxSizeInBytes) {
            $errors[] = sprintf(
                'File is too large. Maximum file size is %d MB.',
                $maxSizeInMB
            );
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'size_in_bytes' => $fileSize,
            'size_in_mb' => round($fileSize / (1024 * 1024), 2),
        ];
    }
}

if (! function_exists('validate_multiple_file_sizes')) {
    /**
     * Validate multiple file sizes in a request
     *
     * @param  int  $maxSizeInMB  Maximum file size in megabytes
     * @param  int  $minSizeInKB  Minimum file size in kilobytes (optional)
     */
    function validate_multiple_file_sizes(array $files, int $maxSizeInMB = 5, int $minSizeInKB = 1): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = [
                'filename' => $file->getClientOriginalName(),
                'validation' => validate_file_size($file, $maxSizeInMB, $minSizeInKB),
            ];
        }

        return [
            'is_all_valid' => collect($results)->every(function ($result) {
                return $result['validation']['is_valid'];
            }),
            'files' => $results,
        ];
    }
}
