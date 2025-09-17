<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Notification Email</title>
</head>

<body>
    <h1>Hello {{ $mailData['employer']->name }}</h1>
    <p>job title: {{ $mailData['job']->title }}</p>
    <p>employe detail: </p>

    <p>name: {{ $mailData['user']->name }}</p>
    <p>email: {{ $mailData['user']->email }}</p>
    <p>mobile no:  {{ $mailData['user']->mobile }}</p>
</body>

</html>