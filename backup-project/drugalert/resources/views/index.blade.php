<!doctype html>
<html lang="th">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#f3f7fb" />
    <meta
      name="description"
      content="Medical Box Monitor - ระบบบริหารกล่องยาฉุกเฉินของโรงพยาบาล"
    />
    <title>Medical Box Monitor</title>
    <link rel="icon" type="image/svg+xml" href="./favicon.svg" />
    <link rel="manifest" href="./manifest.webmanifest" />
    <link rel="stylesheet" href="./styles.css?v={{ md5_file(public_path('styles.css')) }}" />
    <script type="module" src="./app.js?v={{ md5_file(public_path('app.js')) }}"></script>
  </head>
  <body>
    <div id="app"></div>
  </body>
</html>
