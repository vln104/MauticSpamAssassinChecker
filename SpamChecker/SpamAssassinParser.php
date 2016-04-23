<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MauticPlugin\VLNSpamassassincheckerBundle\SpamChecker;

class SpamAssassinParser
{
    public static function parseOutput($spamAssassinTable)
    {
        $output = array();
        
        $lines = explode('\n', $string);
        
        if (!is_empty($lines) && startWith($lines[1], '-'))
        {
            $dashedLines = explodes($lines[1]);
        
            $scoreIndex = 0;
            $ruleIndex = strlen($dashedLines[0]);
            $textindex = ruleIndex + strlen($dashedLines[1] + 1);
            
            foreach ($lines as $lineNumber => $line)
            {
                //ignore first 2 lines which are labels and dashes
                if ($lineNumber > 2)
                {
                    if (!is_empty(trim(substr($line, $ruleIndex, strlen($dashedLines[1])))))
                    {
                        $score = substr($line, $scoreIndex, strlen($dashedLines[0]));
                        $rule = substr($line, $ruleIndex, strlen($dashedLines[1]));
                        $text = substr($line, $textindex, strlen($dashedLines[2]));
                    }
                }
            }
        }

        return $output;
    }
}