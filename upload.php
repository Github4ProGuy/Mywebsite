<?php
require('fpdf/fpdf.php');  // Ensure you have included FPDF library

// Constants for image processing (low quality for faster processing)
define('TARGET_WIDTH', 360);    // Target width in pixels (~1.2 inches at 300 DPI)
define('TARGET_HEIGHT', 420);   // Target height in pixels (~1.4 inches at 300 DPI)
define('A4_WIDTH', 2480);       // A4 width in pixels at 300 DPI
define('A4_HEIGHT', 3508);      // A4 height in pixels at 300 DPI
define('IMAGES_PER_ROW', 6);    // Number of images per row
define('SPACING', 30);          // Spacing between images in pixels

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imageUpload'])) {
    $file = $_FILES['imageUpload'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $file['error']);
    }

    // Check if the file is an image and if it was uploaded correctly
    if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        die("No file was uploaded or the file is missing.");
    }

    // Get image info to verify the uploaded file is an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        die("Uploaded file is not a valid image.");
    }

    // Load the uploaded image
    $sourceImage = imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$sourceImage) {
        die("Failed to load the image.");
    }

    // Resize the image to the specified dimensions
    $resizedImage = imagecreatetruecolor(TARGET_WIDTH, TARGET_HEIGHT);
    imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, TARGET_WIDTH, TARGET_HEIGHT, imagesx($sourceImage), imagesy($sourceImage));

    // Create the A4 layout image
    $a4Image = imagecreatetruecolor(A4_WIDTH, A4_HEIGHT);
    $white = imagecolorallocate($a4Image, 255, 255, 255);
    imagefill($a4Image, 0, 0, $white);

    // Position images in a single row at the top of the A4 sheet
    $x = SPACING;
    $y = SPACING;  // Position at the top with some spacing

    for ($i = 0; $i < IMAGES_PER_ROW; $i++) {
        imagecopy($a4Image, $resizedImage, $x, $y, 0, 0, TARGET_WIDTH, TARGET_HEIGHT);
        $x += TARGET_WIDTH + SPACING;
    }

    // Save the low-quality A4 layout as a JPG with 70% quality
    $outputPath = 'output/a4_layout.jpg';
    imagejpeg($a4Image, $outputPath, 70);  // 70 is the quality setting for lower quality and faster processing

    // Create a PDF with low-quality images (using the same 70% JPG)
    $pdf = new FPDF('P', 'pt', [A4_WIDTH, A4_HEIGHT]);  // 'pt' for points, to keep the resolution
    $pdf->AddPage();
    $pdf->Image($outputPath, 0, 0, A4_WIDTH, A4_HEIGHT);  // A4 in points for low quality
    $pdfOutputPath = 'output/a4_layout_.pdf';
    $pdf->Output($pdfOutputPath, 'F');

    // Clean up temporary images
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
    imagedestroy($a4Image);

    // Display download links
    echo "<h3>Download Your A4 Layout:</h3>";
    echo "<a href='$outputPath' download='a4_layout.jpg'>Download Image</a><br>";
    echo "<a href='$pdfOutputPath' download='a4_layout.pdf'>Download PDF</a>";
} else {
    echo "Please upload an image.";
}
?>
