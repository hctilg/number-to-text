<?php

$number_to_text = require_once('number-to-text.php');

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
  $test_cases = [];
  
  foreach ([
    '-0'=> 'صفر',
    '-0.16'=> 'منفی شانزده صدم',
    '-756.42'=> 'منفی هفتصد و پنجاه و شش و چهل و دو صدم',
    '-9009009009009'=> 'منفی نه تریلیون و نه میلیارد و نه میلیون و نه هزار و نه',
    '2000000'=> 'دو میلیون',
    '987654321012345678987654321012345678'=> 'نهصد و هشتاد و هفت دسیلیون و ششصد و پنجاه و چهار نونیلیون و سیصد و بیست و یک اکتیلیون و دوازده سپتیلیون و سیصد و چهل و پنج سکستیلیون و ششصد و هفتاد و هشت کوینتیلیون و نهصد و هشتاد و هفت کوادریلیون و ششصد و پنجاه و چهار تریلیون و سیصد و بیست و یک میلیارد و دوازده میلیون و سیصد و چهل و پنج هزار و ششصد و هفتاد و هشت',
    '-4713986205.11'=> 'منفی چهار میلیارد و هفتصد و سیزده میلیون و نهصد و هشتاد و شش هزار و دویست و پنج و یازده صدم',
    '200000000000000000000000000000000000.000000000002'=> 'دویست دسیلیون و دو تریلیونم',
    '0254'=> 'دویست و پنجاه و چهار',
    '10000'=> 'ده هزار',
    '01000'=> 'یک هزار',
    '100.003'=> 'صد و سه هزارم',
    '-۷.۱۱'=> 'منفی هفت و یازده صدم'
  ] as $input => $output) $test_cases[] = [
    'input'=> $input,
    'output'=> $output
  ];

  $test_counter = count($test_cases);
  $tested = [];

  foreach ($test_cases as $row) {
    try {
      $output = $number_to_text("$row[input]");
      $row['success'] = md5($output) == md5($row['output']);
      $tested[] = $row;
    } catch (Exception $e) {
      $test_counter--;
    }
  }

  $success = [];
  $errors = [];

  foreach ($tested as $row) ($row['success']? $success[] = $row : $errors[] = $row);

  $success_counter = count($success);
  $error_counter = count($errors);
    
  $success_number = (($test_counter - $error_counter) * 100) / $test_counter;
  $error_number = 100 - $success_number;

  if (php_sapi_name() == 'cli') {
    echo "\n  [*] success number: $success_number% \n";
    echo "\n  [*] error number: $error_number% \n";
    if ($error_counter > 0) {
      echo "\n  [!] errors: \n";
      foreach ($errors as $i => $row) {
        $i++;
        echo "\n    $i. $row[input] => \"$row[output]\" \n";
      }
    }
    echo "\n";
  } else {
    echo "
<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title> تست </title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap');

* {
  box-sizing: border-box;
  margin: 0px;
  padding: 0px;
  outline: 0px;
}

html, body {
  width: 100vw;
  min-height: 100vh;
  overflow: hidden auto;
  background-color: #fafafa;
  color: #1e1e1e;
}

html {
  color-scheme: light;
}

body {
  font-family: 'Vazirmatn', sans-serif;
  font-size: 18px;
}

#container {
  position: absolute;
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #F5F5F5;
  border-radius: 8px;
  width: calc(100% - 2em);
  max-width: 635px;
  min-height: 50px;
  display: flex;
  flex-flow: column nowrap;
  box-shadow: 0 0 2px 1px rgba(200 ,200 ,200 , 0.4);
  overflow: hidden;
}

#container #number {
  text-align: left;
  direction: ltr;
  font-size: 18px;
  font-family: sans-serif;
  background-color: #fff;
  width: 100%;
  height: 50px;
  padding: 10px 16px;
  border: 0px;
  border-bottom: 1px solid rgba(100 ,100 ,100 , 0.2);
}

#container #number_text {
  padding: 14px 18px 14px 20px;
}
    </style>
  </head>
  <body dir='rtl'>
    <div id='container'>
      <input type='text' dir='rtl' title='عدد مورد نظر خود را وارد کنید.' maxlength='128' pattern='^[\-0\.123456789]+$' id='number' value='' autocomplete='false' translate='no' spellcheck='false' required/>
      <p id='number_text'>عدد مورد نظر خود را وارد کنید.</p>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', ev => {
  document.getElementById('number').onkeyup = ev => {
    var number = (ev.srcElement || ev.target).value ?? '';
    var number_text = document.getElementById('number_text');
    if (number.trim() === '' || !(new RegExp(/^[\-0\.123456789۰۱۲۳۴۵۶۷۸۹]+$/).test(number))) {
      number_text.innerText = 'عدد مورد نظر خود را وارد کنید.';
      return;
    }
    const formData = new FormData();
    formData.append('number', number);
    fetch('number-to-text.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
      if (!!data.ok) {
        number_text.innerText = data.result;
      } else {
        if (data.error_code == 400) number_text.innerText = 'لطفا از اعداد انگلیسی استفاده کنید!';
        else if (data.error_code == 413) number_text.innerText = 'عدد شما از حد مجاز بیشتر است!';
        else number_text.innerText = 'خطا';
      }
    })
    .catch(error => {
      number_text.innerText = 'اشکال در برقراری ارتباط';
    });
  };
});
    </script>
  </body>
</html>
";
  }
}