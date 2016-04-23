<?php

/* 
 * @copyright   2016 Linh Nguyen (vln104) at mk8r.com. All rights reserved.
 * @author      Linh Nguyen
 */

namespace MauticPlugin\VLNSpamassassincheckerBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

class VLNSpamassassincheckerBundle extends PluginBundleBase
{
    public function getParent()
    {
        return 'MauticEmailBundle';
    }
}

