<?php

class Inchoo_SocialConnect_Block_Facebook_Adminhtml_System_Config_Form_Field_Links
    extends Inchoo_SocialConnect_Block_Adminhtml_System_Config_Form_Field_Links
{

    protected function getAuthProviderLink()
    {
        return 'Facebook Developers';
    }

    protected function getAuthProviderLinkHref()
    {
        return 'https://developers.facebook.com/';
    }

}