<?php

class Inchoo_SocialConnect_Block_Twitter_Adminhtml_System_Config_Form_Field_Redirects
    extends Inchoo_SocialConnect_Block_Adminhtml_System_Config_Form_Field_Redirects
{

    protected function getAuthProvider() 
    {
        return 'twitter';
    }

}