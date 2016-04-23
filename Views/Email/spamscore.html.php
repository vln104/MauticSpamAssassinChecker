<?php

/* 
 * @copyright   2016 Linh Nguyen (vln104) at mk8r.com. All rights reserved.
 * @author      Linh Nguyen
 */
?>

<div>
    Summary score: <b><?php echo $summaryScore ?></b>
    <br/>
    <br/>
    A score of 5 or above is normally considered spam. Refer to the table below for breakdown of score. Note that emails being submitted here have not been processed by mail servers so are missing headers which could result in a high score. It is best to ignore this score component and ensure that your mail server is properly setup with SPF and DKIM to maximise deliverability.
    <br/>
    <br/>
    <font face="Courier"><?php echo $spamDetail ?></font>
    <br/>
    Refer to SpamAssassim's wiki for the <a href="https://wiki.apache.org/spamassassin/Rules/" target="_ blank">list of tests</a>
    <br/>
    <br/>
    This plugin uses the Postmark SpamAssassin API - many thanks!
</div>