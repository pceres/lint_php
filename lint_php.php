<?php
/*

    php_lint: a PHP script that computes McCabe's cyclomatic complexity of a generic PHP source code
    Copyright (C) 2007  Pasquale Ceres
    Version 0.12rc1

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



$text = $_POST['lint_text'];
$action = $_REQUEST['lint_action'];
$debug_level = $_REQUEST['lint_debug_level'];

if (get_magic_quotes_gpc())
{
	// magic_quotes_gpc abilitato
	$text = stripslashes($text);
	$action = stripslashes($action);
	$debug_level = stripslashes($debug_level);
}

// folder on server containing this script
$script_filename = $_SERVER['SCRIPT_FILENAME'];
$workdir = dirname($script_filename).'/';

// start action parsing
if ($action === 'download')
{
	print_redirect_header("lint_php.zip");
	$counter3 = log_action($workdir,"download");
	
	print_html_header();
	echo "lint_php was downloaded $counter3 times.";
	print_html_footer();
}
elseif (empty($text))
{
	$counter1 = log_action($workdir,"welcome");
	show_welcome_page($counter1,$debug_level);
}
else
{
	// main calculation
	$result = lint($text,$debug_level);
	
	
	print_html_header();
	
	echo "<h3>Cyclomatic complexity:</h3>\n";
	$text = $result[0]['res_lint'];
	foreach ($text as $line)
	{
		echo("$line<br>\n");
	}
	
	echo "<br><hr>\n";
	echo "<h3>Analisys details:</h3>\n";
	
	
	$struc = $result[0]['lines_out'];
	show_lines2($struc['lines'],$struc['numlines'],$struc['list_indent_out'],0,10000,$struc['indent_mccount']);
	
	print_html_footer();
	
	$counter2 = log_action($workdir,"results");
	echo "<br><hr>\n";
	echo "Page viewed $counter2 times.\n";
}

// end of main code here


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_welcome_page($counter,$debug_level) {
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
<title>McCabe's cyclomatic complexity</title>
</head>

<body>

<form method='post' action='lint_php.php<?php if (!empty($debug_level)) {echo "?lint_debug_level=$debug_level";} ?>'>

Insert your PHP code below:<br>
<textarea name='lint_text' cols="150" rows="25"></textarea>

<br><br>
<!--
Debug type:
<select name='lint_debug_level'>
	<option>0</option>
	<option>1</option>
	<option>2</option>
</select>

<br><br>-->

<input type='submit' value='Compute complexity'><br>
</form>
<br>

<br><b>How does it work?</b><br>
Imagine you have a <a href="http://www.php.net/">PHP</a> code inside '&lt;?php' and '?&gt;' tags: you can just copy and paste the whole text of the PHP
file inside the text area above and click on "Compute complexity": the script will:<br>
 - first "distill" the sole PHP code, dropping everything outside '&lt;?php' and '?&gt;' tags (that is HTML and Javascript), comments, strings, etc.,<br> 
 - then find the functions inside, and compute the <a href="http://en.wikipedia.org/wiki/Cyclomatic_complexity">McCabe's cyclomatic complexity</a> for each one. The code outside any function will account for a virtual main function, and will always be present;<br>
 - an indented version of the input code (sole PHP code) will be output, hilighting the lines that contribute to increase complexity: this can help you to refactor your code and split it into smaller functions, whose single complexity is smaller.<br>

<br><b>How to interpret its output?</b><br>
If you enter the following PHP code in the text area:
<pre>
&lt;?php 

if ($error == 1)
{
    die('error')
}
else
{
    show_welcome_page(1,1,1);
}

//////////////////////////////////
function show_welcome($verbose,$cond,$unused_var)
{

echo "Some text";

if ( ($verbose == 1) &amp;&amp; $cond)
{
    echo "...and some verbose text";
}
elseif ($verbose == 2)
{
    echo "...and all verbose text";
}

} // end function show_welcome_page($verbose)
?&gt;
</pre>

the output of the script will be:

<pre>
Cyclomatic complexity:
The McCabe complexity is 2.
The McCabe complexity of 'show_welcome' is 4.
        Input variable '$unused_var' appears never to be used.

Analisys details:
Line	MC count	Code
3 	(->1) 	if ($error == 1),{
5 		:        die('_')
6 		},else;,{
9 		:        show_welcome_page(1,1,1);
10 		}
13 		function show_welcome($verbose $cond $unused_var),{
16 		echo "_";
18 	(->2) 	if ( ($verbose == 1) &amp;&amp; $cond),{
20 		:        echo "_";
21 	(->1) 	},elseif ($verbose == 2),{
24 		:        echo "_";
25 		}
27 		}
28 		;
</pre>

This output means that a function "show_welcome" has been found, and that input variable $unused_var has not been used in the body of the function: that could be an error, and should be checked.
The complexity of the function is 4 because it contains two "if" instructions, the first with 2 and the second with 1 logic condition: the McCabe complexity is given by 1 (standard complexity for simple sequential code) increased by 2+1 --> 4.
The code external to any function body has an if statement (with only one logic condition inside), so the complexity is given by 1 increased by 1 --> The McCabe complexity is 2. (here no function name is given, since a virtual main() function is assumed.<br>


<br><b>Why this page?</b><br>
I first found the cyclomatic comlexity concepts in Matlab environment, where a Matlab static code analyzer exists, called mlint. By using command "mlint -cyc &lt;filename&gt;", a cyclomatic complexity evaluation of all functions present in Matlab script &lt;filename&gt; will be performed. This analisys greatly helps you to keep the code clean and easily maintainable.<br>
To help improve <a href="//ars.altervista.org/index.php">my website</a>'s PHP code, I looked for a free tool to compute the McCabe's cyclomatic complexity of a generic PHP (inside HTML) source code, but couldn't find anything, so I decided to do something on my own.<br>
Now the script, though not yet fail-proof, begins to do its job, so I decided to share it on the web: I used PHP developers work for building my website (PHP is a great language!), so I think it is fair to help someone else by giving out the result of my work.<br>

<br><b>Source code of these pages</b><br>
You can download the whole lint_php source code as a <a href="lint_php.php?lint_action=download">compressed archive</a> or from a <a href="http://github.com/pceres/lint_php">git repository</a> ("git clone git@github.com:pceres/lint_php.git" if you like command line and want to collaborate!): it is under GPL v3, so you can study it, modify it, use it as you want, but you must give the same rights to the people to which you redistribute it.<br>
If you have an Apache-PHP system (virtually every GNU/Linux distribution), you can use the script offline, without using an Internet connection: this is the way I use it on my laptop, powered by a Slackware GNU/Linux distribution!<br>

<br><b>History</b><br>
<ul>
<li>rev 0.1: first issue; (04.11.2007)</li>
<li>rev 0.2: html closing tag are ignored inside strings; (08.11.2007)</li>
<li>rev 0.3: multiline string management; (12.11.2007)</li>
<li>rev 0.4: new state (rem_state) added for heredoc string management; (22.11.2007)</li>
<li>rev 0.5: bug fixing to properly manage (and cohordinate) different types of strings and comments; (23.11.2007)</li>
<li>rev 0.6: bug fixing for PHP 5 compatibility (array_merge only accepts arrays as arguments); (18.06.2008)</li>
<li>rev 0.7: function close tag now correctly detected (bug signalled by Charlew Rowe); (01.02.2009)</li>
<li>rev 0.8: added management of public, protected and private functions (bug signalled by Anders Pallisgaard);  (21.06.2009)</li>
<li>rev 0.8.1: bug fixing for protected functions (solution by Anders Pallisgaard); (21.06.2009)</li>
<li>rev 0.8.2: bug fixing for return-by-reference functions (bug signalled by Charlew Rowe); (17.12.2009)</li>
<li>rev 0.8.3: bug fixing for comments inside function parameters (bug signalled by Charlew Rowe); (20.12.2009)</li>
<li>rev 0.9: added management of ternary statements (bug signalled by Charlew Rowe); (23.12.2009)</li>
<li>rev 0.10: bug fixing of multi-line ternary statements with comments inside; (29.12.2009)</li>
<li>rev 0.11: bug fixing of /*...*/ comments in HTML\Javascript code; (06.01.2010)</li>
<li>rev 0.12: deprecated ereg functions replaced with preg counterparts; (28.02.2010)</li>
</ul>

<br><b>How can you contribute?</b><br>
Writing this code took me pretty much time, so I'll be glad to know someone is using it!<br>
Also let me know about any bugs, PHP source code that makes the script fail, or, even better, improvements to the code: I'll be happy to include them into lint_php!<br>
You can email me, or just write a quick comment <a href="http://collabedit.com/display?id=86168">here</a>.

<br><br>
Pasquale Ceres<br>
Caposele (Avellino) - Italy<br>
pasquale_c at hotmail dot com<br>
GPG Public key id: 5FF1B40D; fingerprint: 2529 8665 212D D704 96DF  21EB 6531 8E32 5FF1 B40D<br>

<br>
Webmaster of <a href="http://ars.altervista.org/PhpGedView/index.php">Genealogia di Caposele</a><br>
Webmaster of <a href="http://pceres.altervista.org/stralaceno/index.php">StralacenoWeb</a><br>
Webmaster of <a href="http://ars.altervista.org">ArsWeb</a><br>

<hr>
Page viewed <?php echo $counter; ?> times.

</body>

</html>

<?php
} // end function show_welcome_page



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_lines2($list_lines,$list_numlines,$list_indent,$only_code,$max_lines,$indent_mccount = Array()) {

echo "<table>\n";
echo "<thead><tr><th>Line</th><th>MC count</th><th>Code</th></tr></thead>\n";
echo "<tbody>\n";
for ($i=0;$i<min(count($list_lines),$max_lines);$i++)
{
	echo "<tr><td>\n";

	$line = htmlentities($list_lines[$i]);
	$numline = $list_numlines[$i];
	$indent = $list_indent[$i];
	
	$num_spaces = $indent*8;
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
			$ks_mccount = '      </td><td> ';
		}
	}
	else
	{
		$ks_mccount = '';
	}
	
	// function line spacing
	if (substr($line,0,8) === 'function')
	{
		echo "&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n<tr><td>\n";
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

if (empty($workdir))
{
	$script_filename = $_SERVER['SCRIPT_FILENAME'];
	$workdir = dirname($script_filename).'/';
}
$logfile = $workdir.'logfile.txt';

$num_params_per_tag = 3; // number of parameters for each line (es.: "welcome::5635::20/12/2009 22:12:02")

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
		$var = split("::",trim($line));
		if (!empty($var[1])) // parse lines with at least two parameters
		{
			$logdata[$var[0]] = array_slice($var,1);
		}
	}
}

// edit logdata

$item = $logdata[$string];

$ks_date = date("d/m/Y H:m:s",time()); // current date and time
$counter = $item[0]+1;
$item[0] = $counter;
$item[1] = $ks_date;

$logdata[$string] = $item;

// write logdata
$fid = fopen($logfile,'w');

if ($fid !== False)
{
	foreach ($logdata as $tag => $value_array)
	{
		// assemble line
		$ks = $tag;
		foreach ($value_array as $value)
		{
			$ks .= "::".$value;
		}
		
		$ks .= "\n";
		fwrite($fid,$ks);
	}
	fclose($fid);
}
else
{
	die("File I/O error writing $logfile");
}

return $counter;

}


?>
