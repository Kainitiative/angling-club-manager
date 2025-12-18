<?php
declare(strict_types=1);

function processLogoUpload(array $file, string $uploadDir, int $maxSize = 200, int $quality = 85): array {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        ];
        throw new Exception($errors[$file['error']] ?? 'Upload error');
    }
    
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File too large (max 5MB)');
    }
    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Invalid image file');
    }
    
    $mime = $imageInfo['mime'];
    if (!in_array($mime, $allowedMimes)) {
        throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    if ($width > $maxSize || $height > $maxSize) {
        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = (int)(($maxSize / $width) * $height);
        } else {
            $newHeight = $maxSize;
            $newWidth = (int)(($maxSize / $height) * $width);
        }
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if ($newImage === false) {
        throw new Exception('Failed to create image canvas');
    }
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            imagedestroy($newImage);
            throw new Exception('Unsupported image type');
    }
    
    if ($sourceImage === false) {
        imagedestroy($newImage);
        throw new Exception('Failed to load image');
    }
    
    imagecopyresampled(
        $newImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = ($type === IMAGETYPE_PNG) ? 'png' : 'jpg';
    $fileName = 'logo_' . uniqid() . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;
    
    if ($type === IMAGETYPE_PNG) {
        $result = imagepng($newImage, $destination, 6);
    } else {
        $result = imagejpeg($newImage, $destination, $quality);
    }
    
    imagedestroy($newImage);
    imagedestroy($sourceImage);
    
    if (!$result) {
        throw new Exception('Failed to save image');
    }
    
    return [
        'filename' => $fileName,
        'path' => $destination,
        'url' => '/uploads/logos/' . $fileName,
        'width' => $newWidth,
        'height' => $newHeight
    ];
}

function processCatchUpload(array $file, string $uploadDir, int $maxWidth = 800, int $quality = 80): array {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 10 * 1024 * 1024;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error');
    }
    
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File too large (max 10MB)');
    }
    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Invalid image file');
    }
    
    $mime = $imageInfo['mime'];
    if (!in_array($mime, $allowedMimes)) {
        throw new Exception('Invalid file type');
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)(($maxWidth / $width) * $height);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            throw new Exception('Unsupported type');
    }
    
    imagecopyresampled(
        $newImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = 'catch_' . uniqid() . '.jpg';
    $destination = $uploadDir . '/' . $fileName;
    
    imagejpeg($newImage, $destination, $quality);
    
    imagedestroy($newImage);
    imagedestroy($sourceImage);
    
    return [
        'filename' => $fileName,
        'path' => $destination,
        'url' => '/uploads/catches/' . $fileName,
        'width' => $newWidth,
        'height' => $newHeight
    ];
}

function processGalleryUpload(array $file, string $uploadDir, int $maxWidth = 1200, int $quality = 85): array {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 10 * 1024 * 1024;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error');
    }
    
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File too large (max 10MB)');
    }
    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Invalid image file');
    }
    
    $mime = $imageInfo['mime'];
    if (!in_array($mime, $allowedMimes)) {
        throw new Exception('Invalid file type');
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)(($maxWidth / $width) * $height);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            throw new Exception('Unsupported type');
    }
    
    imagecopyresampled(
        $newImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = 'gallery_' . uniqid() . '.jpg';
    $destination = $uploadDir . '/' . $fileName;
    
    imagejpeg($newImage, $destination, $quality);
    
    imagedestroy($newImage);
    imagedestroy($sourceImage);
    
    return [
        'filename' => $fileName,
        'path' => $destination,
        'url' => '/uploads/gallery/' . $fileName,
        'width' => $newWidth,
        'height' => $newHeight
    ];
}
