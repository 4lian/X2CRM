<?php

/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2015 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

Yii::import('application.components.util.*');

/**
 * Builds a php theme file from various @theme tags in all CSS files. 
 * The syntax for a theme tage is 
 *   /* @theme <rule> <key> */ 
 /* Where <rule> is a css rule that takes a color argument, 
 * and <key> is a themeGenerator key found at the bottom of {@link ThemeGenerator}
 * This script will find all the tags and accumulate the rules in a php file
 *
 * @package application.commands
 * @author Alex Rowe <alex@x2engine.com>
 */
class ThemeBuildCommand extends CConsoleCommand {

    /**
     * @var string Input directory of the css Root
     */
    public $inputDir = '../';

    /**
     * @var string Output file
     */
    public $outputFile = 'components/ThemeGenerator/templates/generatedRules.php';

    /**
     * Entry point
     */
    public function run($args) {
        if(isset($args[0])) {
            $this->inputDir = $args[0];
        }

        if(isset($args[1])) {
            $this->outputFile = $args[1];
        }

        if(isset($args[0]) && $args[0] == '-keys') {
            echo "These are the avaliable theming keys\n";

            foreach(ThemeGenerator::getProfileKeys() as $key) {
                echo "$key\n";
            }

            return;
        }

        echo "Building theme...\n";
        // First, we recieve a list of all CSS files
        $paths = $this->getCssFiles ($this->inputDir);

        $length = count($paths);

        if ($length < 1) {
            echo "Error: no Css files found in directory: $this->inputDir";
            return;
        }

        echo "$length css files found\n";
        echo "Scanning for theme tags...";
        $counter = 0.0;

        // Now, we collect the rules from each file, merging duplicate entries
        $matches = array();
        foreach ($paths as $i => $path) {
            $matches = array_merge($matches, $this->scanCssFile($path));
            
            // print loading status....
            while ($counter < $i/$length) {
                echo '.';
                $counter+= 0.1;
            }
        }

        $matchesLength = count($matches);
        echo "done\n$matchesLength rules found\n";

        if ($matchesLength < 1) {
            echo "No rules found, aborting\n";
            return;
        }

        echo "Formatting rules...\n";

        // Finally, construct a string from all of the rules
        $output = "<?php return \"\n"; // php header
        foreach ($matches as $selector => $rule) {
            $output .= $this->formatRule($selector, $rule);
        }
        $output .= "\n \"; ?>"; // Footer

        echo "Saving to $this->outputFile\n";
        file_put_contents ($this->outputFile, $output);
    }

    /**
     * Gets a list of all css files in the directory, recusrively
     * @param $root string Path of the root directoy
     * @return array list of full paths
     */
    public function getCssFiles($root) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        $paths = array($root);
        foreach ($iter as $path => $dir) {
            if (preg_match('/\.css$/', $path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Scans a css file and for theme tags and formats an array of rules
     * @param $path string pathname of a file to scan
     * @return Array of rules in the following format: 
     *       '<Selector>' =>                    // ex. div.icon
     *               'comments' => 
     *                     '<comment1>',        // ex. line 223 of css
     *                     ...
     *               0 =>   
     *                     'rule' => <rule>     // ex. background
     *                     'value' => <value>   // ex. darker_link
     *               ...
     */
    public function scanCssFile($path) {
        $handle = fopen($path, "r");
        $rules = array();

        $lineNumber = -1;
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            if (!preg_match('/@theme/', $line)) continue;

            list($selector, $comment, $rule) = $this->makeRule($path, $lineNumber);

            // Create a new rule if its not already in the list
            if (!isset($rules[$selector])) {
                $rules[$selector] = array($rule);
                $rules[$selector]['comments'] = array($comment);
                continue;
            } 

            // checks for duplicate rules
            if (in_array($rule, $rules[$selector])) continue;
            $rules[$selector][] = $rule;

            // checks for duplicate comments
            if (in_array($comment, $rules[$selector]['comments'])) continue;
            $rules[$selector]['comments'][] = $comment;
        }       

        fclose($handle);

        return $rules;
    }

    /**
     * @param $file string pathname of a css file
     * @param $lineNumber int lineNumber of the theme tag
     * @return array of needed items to construct the array seen in {@link scanCssFile}.
     */
    public function makeRule($file, $lineNumber) {
        $lines = file($file);
        $themeLine = $lines[$lineNumber];
        // print_r($themeLine);/
        $stripped = preg_replace('/.*@theme\ *(.*)\*\//', '\1', $themeLine);

        // Remove extra spaces in between
        $stripped = preg_replace('/\ \ */', ' ', $stripped);
        $stripped = preg_replace('/:/', '', $stripped);
        $params = split(' ', $stripped);


        while(!preg_match('/{/', $lines[$lineNumber])) {
            $lineNumber--;
        }

        $selector= $lines[$lineNumber];
        $comment = $lines[$lineNumber - 1];
        $rule = $params[0];
        $value = $params[1];

        return array ( 
                $selector, 
                $comment, 
                array (
                    'rule' => $rule, 
                    'value' => $value
                )
            );

    }

    /**
     * Formats a rule array into CSS
     * @param string $selector CSS selector to put the rules under
     * @param string $rule array of comments and items to put into the CSS
     */
    public function formatRule($selector, $rule) {
        // Comments is a 'special' entry in the array, so we take it out before iterating
        $comments = $rule['comments'];
        unset($rule['comments']);
        $string = "\n";

        foreach($comments as $index => $comment) {
            $string .= "\t$comment";
        }

        $string .= "\t$selector";

        foreach($rule as $value) {
            $string .= "\t\t".$value['rule'].': $colors['.$value['value']."]\n";
        }
        $string .= "\t}\n";

        return $string;
    }

    public function getHelp() {
        return "\nBuilds a php theme file from various @theme tags in all CSS files. \nUsage: themebuild [INPUT DIRECTORY] [OUTPUT FILE]\n\nOptions: themebuild -keys \n\t This will list all the avaliable keys for theming.\n";
    }

}

?>
