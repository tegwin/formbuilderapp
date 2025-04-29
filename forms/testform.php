<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Simple Form</title>
</head>
<body>
<h1>Test Simple Form</h1>

<form method="post" action="testsubmit.php" enctype="multipart/form-data">
    <div>
        <label>Name:</label>
        <input type="text" name="name">
    </div>
    <div>
        <label>Upload File:</label>
        <input type="file" name="file_upload">
    </div>
    <button type="submit">Submit</button>
</form>

</body>
</html>
