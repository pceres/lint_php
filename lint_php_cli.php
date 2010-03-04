#! /usr/bin/php
<?php
/*

    lint_php_cli: a PHP script that computes McCabe's cyclomatic complexity of a generic PHP source code
    Copyright (C) 2007  Pasquale Ceres
    Version 0.13

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

/*

This script was based on lint.php from phpcyclo project (http://www.ohloh.net/p/phpcyclo)

*/

require_once('lint_php_lib.php');

// var_dump($argv);
// var_dump($argc);
// die('a');

// For large files
set_time_limit(120);

if ($argc < 2) 
{
	show_usage_page();
	die("Not enough arguments.\n");
}
else
{
	$filename  = $argv[1];

	if ($argc > 2)
	{
		$arguments = $argv[2];
		
		if ($arguments === '-v')
		{
			$verbose_level = 1;
		}
		elseif ($arguments === '-vv')
		{
			$verbose_level = 2;
		}
		else
		{
		var_dump($argv);
			show_usage_page();
			die("Wrong arguments.\n");
		}
	}
	else
	{
		$verbose_level = 0;
	}
}

$text = file_get_contents($filename); // read file
$debug_level = 0; // no output during parsing

if (empty($text))
{
	die("File is empty\n");
}
else
{

// main calculation
$result = lint($text,$debug_level);

// sort functions by complexity
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


if ($verbose_level > 0)
{
	echo "\n\n\n";
	echo "------------------------------\n";

	$text = $result[0]['res_lint'];
	foreach ($text as $line)
	{
		echo("$line\n");
	}

	if ($verbose_level > 1)
	{
		echo "------------------------------\n";
		echo "Analisys details:\n";

		$struc = $result[0]['lines_out'];
		show_lines2($struc['lines'],$struc['numlines'],$struc['list_indent_out'],0,10000,$struc['indent_mccount']);
	}
}

echo "\n";
echo "\n";

}

// end of main code here



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_usage_page() {
echo <<<END
SYNOPSIS
       lint_php_cli.php [-v] [-vv] <php script>

DESCRIPTION
       This script parses the php file <php script>, and calculates the cyclomatic complexity of its functions

OPTIONS
      Without options -v and -vv, only the MC count for each function (and the virtual main) is shown.
      -v     Also show any messages (unused inputs\\outputs\\functions)
      -vv    Besides the -v output, also show the indented, pruned code, showing the points that increase the MC count

EXAMPLES
      Verbose output over file lint_php.php:
          lint_php_cli.php lint_php.php -vv
 
      Recursive analysys of all files inside current folder
          find . -name '*.php' -exec sh -c 'echo -e "\\n{} :\\n" && lint_php_cli.php {}' \\; | less

END;
} // end function show_usage_page



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_lines2($list_lines,$list_numlines,$list_indent,$only_code,$max_lines,$indent_mccount = Array()) {

echo "Line\tMC count\tCode\n";

for ($i=0;$i<min(count($list_lines),$max_lines);$i++)
{
	$line = $list_lines[$i];
	$numline = $list_numlines[$i];
	$indent = $list_indent[$i];

	$num_spaces = $indent*8;
	$indent_str = str_repeat(':'.str_repeat("\t",1),$indent); // indentazione

	$mc_count = $indent_mccount[$i];
	if ($mc_count > 0)
	{
		$ks_mccount = sprintf("(->%1d) \t ", $mc_count);
	}
	else
	{
		$ks_mccount = " \t ";
	}

	if ($only_code)
	{
		echo(sprintf("%s\t%s%s",$ks_mccount,$indent_str,$line));
	}
	else
	{
		echo(sprintf("%3d\t%s\t%s%s",$numline,$ks_mccount,$indent_str,$line));
	}
	echo "\n";
} // end for $i

echo "\n";

} // end function show_lines2


?>
