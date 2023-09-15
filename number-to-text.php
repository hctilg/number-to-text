<?php

// check php version
if (version_compare(phpversion(), '5.4', '<')) {
  die("It requires PHP 5.4 or higher. Your PHP version is " . phpversion() . PHP_EOL);
}

/**
 * number validator
 * @param string|int|float $number
 * @return bool
 */
function number_validator(string $number) {
  if (in_array($number, ['-', '.', ''])) return false;

  $validated_chars = str_split('-0.123456789');
  foreach (str_split($number) as $char) {
    if (!in_array($char, $validated_chars)) return false;
  }
  
  if (substr_count($number, '.') > 1) return false;
  if (substr_count($number, '-') == 1 && strpos($number, '-') != 0) return false;

  return substr_count($number, '.') < 2;
}

/**
 * split number into groups of 3 digits
 * @return array;
 */
function number_grouper($number) {
  $groups = [];
  while ($number != '') {
    $groups[] = substr($number, -3);
    $number = substr($number, 0, -3);
  }
  return array_reverse($groups);
}

function str_clean(string $string): string {
  return preg_replace(["/\n+|\s+/", "/\s{2,}/"], [' ', ' '], trim($string));
}

function number_clean($number, $decimal = false) {
  $number = $decimal ? rtrim($number, '0') : ltrim($number, '0');
  $number = $number == '' ? '0' : $number;
  return $number;
}

/**
 * raplace chras
 * @param string $string
 * @param array $old
 * @param array $new
 * @return string
 */
function replace_chars(string $string, array $old, array $new) {
  if (count($old) !== count($new)) throw new LengthException("Number of elements in lists don't match.", 1);
  foreach ($old as $index => $char) $string = str_replace($char, $new[$index], $string);
  return $string;
}

/**
 * converts numbers to persian text.
 * @param string|int|float $number
 * @return string
 */
function number_to_text($number) {

  // strip whitespace
  $number = trim("$number");

  // 
  $number = replace_chars($number, str_split('۰۱۲۳۴۵۶۷۸۹', 2), str_split('0123456789', 1));

  if (!number_validator($number)) {
    throw new Exception('parameter type `number` is not a number.', 400);
  }

  $is_negative = substr($number, 0, 1) === '-';

  $number_group = explode('.', $number);
  
  $integers = empty($number_group[0]) ? '0' : $number_group[0];
  $decimals = empty($number_group[1]) ? '0' : $number_group[1];

  if ($is_negative) $integers = substr($integers, 1);

  $integers = number_clean($integers);

  if ($integers >= (10 ** 36)) {
    throw new OverflowException('number too large.', 413);
  }

  $decimals = number_clean($decimals, true);

  // check the decimal isn't too long.
  if (strlen($decimals) > 12) $decimals = substr($decimals, 0, 12);

  // define arrays for number to text converter

  $ones = [
    0  => 'صفر',
    1  => 'یک',
    2  => 'دو',
    3  => 'سه',
    4  => 'چهار',
    5  => 'پنج',
    6  => 'شش',
    7  => 'هفت',
    8  => 'هشت',
    9  => 'نه',
    10 => 'ده',
    11 => 'یازده',
    12 => 'دوازده',
    13 => 'سیزده',
    14 => 'چهارده',
    15 => 'پانزده',
    16 => 'شانزده',
    17 => 'هفده',
    18 => 'هجده',
    19 => 'نوزده'
  ];

  $tens = [
    0 => '',
    1 => 'ده',
    2 => 'بیست',
    3 => 'سی',
    4 => 'چهل',
    5 => 'پنجاه',
    6 => 'شصت',
    7 => 'هفتاد',
    8 => 'هشتاد',
    9 => 'نود'
  ];

  $hundreds = [
    0 => '',
    1 => 'صد',
    2 => 'دویست',
    3 => 'سیصد',
    4 => 'چهارصد',
    5 => 'پانصد',
    6 => 'ششصد',
    7 => 'هفتصد',
    8 => 'هشتصد',
    9 => 'نهصد'
  ];

  $thousands = [
    0  => '',
    1  => 'هزار',
    2  => 'میلیون',
    3  => 'میلیارد',
    4  => 'تریلیون',
    5  => 'کوادریلیون',
    6  => 'کوینتیلیون',
    7  => 'سکستیلیون',
    8  => 'سپتیلیون',
    9  => 'اکتیلیون',
    10 => 'نونیلیون',
    11 => 'دسیلیون'
  ];

  if (($integers + $decimals) == 0) return $ones[0];

  // split number into groups of 3 digits
  $integer_groups = number_grouper($integers);
  $integer_groups_count = count($integer_groups);

  # convert each group of 3 digits to text

  $result = '';

  if ($is_negative) $result .= 'منفی ';
  
  foreach ($integer_groups as $index => $group) {
    $group = number_clean($group);

    $group_text = '';
    $integer_groups_count--;
    
    $jump = $group == '0';

    if ($group >= 100) {
      $hundreds_digit = number_clean(explode('.', $group / 100)[0]);
      $group_text .= $hundreds[$hundreds_digit];
      $group = '' . $group % 100;
      if ($group != '0') $group_text .= ' و ';
    }

    if ($group >= 20 || $group == '10') {
      $tens_digit = number_clean(explode('.', $group / 10)[0]);
      $group_text .= $tens[$tens_digit];
      $group = '' . $group % 10;
      if ($group != '0') $group_text .= ' و ';
    }

    if ($group >= 1 && $group <= 19) $group_text .= $ones[$group];

    $result .= $group_text;

    if (!$jump) {
      $result .= ' ' . $thousands[$integer_groups_count];
      if ($integer_groups_count !== 0) {
        $tmp_group = array_slice($integer_groups, $index);
        foreach ($tmp_group as $i => $value) {
          if (!+$value) unset($tmp_group[$i]);
        }
        if (count($tmp_group) !== 1) $result .= ' و ';
      }
    }
  }
  
  if ($decimals != '0') {
    $result .= ($integers == '0') ? ' ' : ' و ';
    
    // split number into groups of 3 digits
    $decimals_groups = number_grouper($decimals);
    $decimals_groups_count = $dgc = count($decimals_groups);
    
    foreach ($decimals_groups as $group) {
      $group = number_clean($group);

      $group_text = '';
      $decimals_groups_count--;
    
      $jump = $group == '0';
      
      if ($group >= 100) {
        $hundreds_digit = number_clean(explode('.', $group / 100)[0]);
        $group_text .= $hundreds[$hundreds_digit];
        $group = '' . $group % 100;
        if ($group != '0') $group_text .= ' و ';
      }
      
      if ($group >= 20 || $group == '10') {
        $tens_digit = number_clean(explode('.', $group / 10)[0]);
        $group_text .= $tens[$tens_digit];
        $group = '' . $group % 10;
        if ($group != '0') $group_text .= ' و ';
      }
      
      if ($group >= 1 && $group <= 19) $group_text .= $ones[$group];
      
      $result .= $group_text;

      if (!$jump) {
        $result .= ' ' . $thousands[$integer_groups_count];
        if ($integer_groups_count != '0') $result .= ' و ';
      }
    }
    
    // ZERO WIDTH NON-JOINER - Codepoint: U+200C
    $zwnj = '‌';
    
    if ($dgc == 1) $zwnj = '';
    
    $dgl = strlen($decimals_groups[0]);
    
    if ($dgl == 1) $result .= $tens[1] . $zwnj;
    elseif ($dgl == 2) $result .= $hundreds[1] . $zwnj;
    elseif ($dgl == 3) $dgc++;
    
    $result .= $thousands[$dgc - 1] . 'م';
  }

  return str_clean($result);
}

// init module
if (basename(__FILE__) != basename($_SERVER["SCRIPT_FILENAME"])) {
  return function($params) {
    return number_to_text($params);
  };
}

// check cli mode
if (php_sapi_name() === 'cli') {
  if (posix_isatty(STDIN)) /* normal execution */ {
    try {
      $input = readline('Say a number: ');
      $output = number_to_text($input);
      echo "Result: $output\n";
    } catch (Exception $e) {
      $error = $e->getMessage();
      echo "Error: $error\n";
    }
  } else /* pipe execution */ {
    $stdin_str = fgets(STDIN);
    try {
      echo number_to_text($stdin_str);
    } catch (Exception $e) {
      echo 'error: ' . $e->getMessage();
    }
  }

  exit;
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$response = ['ok' => true];

if (($_REQUEST['number'] ?? '') === '') {
  $response['ok'] = false;
  $response['error_code'] = 400;
  $response['message'] = 'number is empty';
} else {
  $input = $_REQUEST['number'];
  try {
    $response['result'] = number_to_text($input);
  } catch (Exception $e) {
    $response['ok'] = false;
    $response['error_code'] = $e->getCode();
    $response['message'] = $e->getMessage();
  }
}

http_response_code($response['error_code'] ?? 200);
echo json_encode($response, 448);
