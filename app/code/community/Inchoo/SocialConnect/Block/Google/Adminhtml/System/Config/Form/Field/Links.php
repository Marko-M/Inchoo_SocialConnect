<?php

class Inchoo_SocialConnect_Block_Google_Adminhtml_System_Config_Form_Field_Links
    extends Inchoo_SocialConnect_Block_Adminhtml_System_Config_Form_Field_Links
{

    protected function getAuthProviderLink()
    {
        return 'Google Developers Console';
    }

    protected function getAuthProviderLinkHref()
    {
        return 'https://console.developers.google.com/';
    }
    
}