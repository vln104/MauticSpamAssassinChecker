<?php

/* 
 * @copyright   2016 Linh Nguyen (vln104) at mk8r.com. All rights reserved.
 * @author      Linh Nguyen
 */

namespace MauticPlugin\VLNSpamassassincheckerBundle\SpamChecker;

use MauticPlugin\VLNSpamassassincheckerBundle\SpamChecker\SpamAssassinParser;

/**
 * Class PostmarkSpamChecker
 */
class PostmarkSpamChecker
{
	private $detailInfo;
	private $summaryScore;
        private $success;
	
	public function getDetailInfo()
	{
		return $this->detailInfo;
	}
	
	public function getSummaryScore()
	{
		return $this->summaryScore;
	}
        
        public function getSuccessState()
        {
            return $this->success;
        }
    
    public function calculateSpamScore($emailContent, $email, $user)
    {
       	$url = 'http://spamcheck.postmarkapp.com/filter';
        $fields = array('email' => $emailContent, 'options' => 'long');

        $fields_string = "";
        
	    //foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	    foreach($fields as $key=>$value) 
        { 
            $fields_string .= $key.'='.urlencode($value).'&'; 
        }
		
        rtrim($fields_string, '&');

        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);

        if ($result === FALSE)
        {
             $this->success = FALSE;
        }
        else
        {    
            $jsonObj = json_decode($result);
                        
            $detail = str_replace("\n", "<br/>", $jsonObj->report);
            $detail = str_replace(" ", "&nbsp;", $detail);
            $this->detailInfo = $detail;
            $this->summaryScore = $jsonObj->score;
            $this->success = $jsonObj->success;
        }
    }
}
