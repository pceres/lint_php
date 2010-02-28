<?php
/*

    lint_php_cli: a PHP script that computes McCabe's cyclomatic complexity of a generic PHP source code
    Copyright (C) 2007  Pasquale Ceres
    Version 0.12

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

require_once('lint_php_lib.php');



// For large files
set_time_limit(120);

if ($argc < 2) {
    die('Not enough arguments');
    $filename = $_GET['file'];
} else {
    $filename = $argv[1];
}

//$text = $_POST['lint_text'];
$text = file_get_contents($filename);
// var_dump($text);
// die();
$debug_level = 0;

if (get_magic_quotes_gpc())
{
// magic_quotes_gpc abilitato
$text = stripslashes($text);
$debug_level = stripslashes($debug_level);
}


if (empty($text))
{
  die('File is empty');
}
else
{
// main calculation
$result = lint($text,$debug_level);

//    print_html_header();
foreach ($result as $id_file => $result_file)
{
	foreach ($result_file['lista_functions'] as $id_fcn => $function_info)
	{
		$fcn_name = $function_info['function'];
		$fcn_mc_count = $function_info['mc_count'];
		$fcn_unused_inputs = $function_info['unused_inputs'];
		$fcn_unused_outputs = $function_info['unused_outputs'];
		$fcn_unused_flag = $function_info['unused_flag'];
		
		if (isset($metrics[$fcn_name]))
		{
			$metrics[$fcn_name] = $metrics[$fcn_name]+($fcn_mc_count-1);
		}
		else
		{
			$metrics[$fcn_name] = $fcn_mc_count;
		}
	}
}

// sort by mc_count
arsort($metrics, SORT_NUMERIC);

// var_dump($metrics);
foreach ($metrics as $function => $count)
{
	if (empty($function))
	{
		echo('ALL FILE COUNT: ' . $count . "\n");
	}
	else
	{
		echo($function . '(): ' . $count . "\n");
	}
}

echo "\n";
echo "\n";

}

// end of main code here


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_welcome_page($counter,$debug_level) {
print 'Usage: @php@ lint.php <php script>' . "\n";
} // end function show_welcome_page



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_lines2($list_lines,$list_numlines,$list_indent,$only_code,$max_lines,$indent_mccount = Array()) {

echo "<table>\n";
echo "<thead><tr><th>Line</th><th>MC count</th><th>Code</th></tr></thead>\n";
echo "<tbody>\n";
for ($i=0;$i<min(count($list_lines),$max_lines);$i++)
{
echo "<tr><td>\n";
// $line = "<pre>".htmlentities($list_lines[$i])."</pre>";

$line = htmlentities($list_lines[$i]);
$numline = $list_numlines[$i];
$indent = $list_indent[$i];

$num_spaces = $indent*8;
// $indent_str = str_repeat('--->',$indent); // indentazione
$indent_str = str_repeat(':'.str_repeat('&nbsp;',8),$indent); // indentazione

if (count('indent_mccount') > 0)
{
$mc_count = $indent_mccount[$i];
if ($mc_count > 0)
{
$ks_mccount = sprintf('(->%1d) </td><td> ', $mc_count);
}
else
{
$ks_mccount = ' </td><td> ';
}
}
else
{
$ks_mccount = '';
}

if ($only_code)
{
echo(sprintf('%s%s%s',$ks_mccount,$indent_str,$line));
}
else
{
echo(sprintf('%3d </td><td> %s%s%s',$numline,$ks_mccount,$indent_str,$line));
}
echo "</td></tr>\n";
} // end for $i

echo "</tbody></table>\n";
// stampa(' ');
// stampa('------------------------------------------------------------------------------');

} // end function show_lines2


/////////////////////////////////////////////////////////////////////////////////////////////////////
function print_redirect_header($redirect_url) {
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>lint_php</title>
<meta http-equiv="refresh" content="0;url=<?php echo $redirect_url; ?>" />
</head>
<body>
</body>
</html>
<?php
} // end function show_redirect_header


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function print_html_header() {
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>lint_php</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<meta name="generator" content="kate">
<meta name="description" content="An online tool to compute PHP code McCabe's cyclomatic complexity">
<meta name="keywords" content="PHP, McCabe, Cyclomatic complexity, GPL, Pasquale Ceres, Caposele">
</head>
<body>
<?php
} // end function show_header


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function print_html_footer() {
?>
</body>
</html>
<?php
} // end function show_footer


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function log_action($workdir,$string)
{
/*
$script_filename = $_SERVER['SCRIPT_FILENAME'];
$root_path = dirname($script_filename).'/';
$logfile = $root_path.'logfile.txt';

// read logdata
$file = file($logfile);
if ($file === false)
{
$logdata = Array();
}
else
{
foreach($file as $line)
{
$var = split("::",$line);
if (!empty($var[1]))
{
$logdata[$var[0]] = $var[1];
}
}
}

// edit logdata
$logdata[$string]++;
$counter = $logdata[$string];

// write logdata
$fid = fopen($logfile,'w');
if ($fid !== False)
{
foreach ($logdata as $tag => $value)
{
$ks = "$tag::$value::\n";
fwrite($fid,$ks);
}
fclose($fid);
}
else
{
die("File I/O error writing $logfile");
}
*/
return 1;

}
?>