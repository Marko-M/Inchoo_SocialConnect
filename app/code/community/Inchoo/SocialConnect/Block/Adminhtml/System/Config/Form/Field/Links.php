<?php

abstract class Inchoo_SocialConnect_Block_Adminhtml_System_Config_Form_Field_Links
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function getAuthProviderLink()
    {
        return '';
    }

    protected function getAuthProviderLinkHref()
    {
        return '';
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $this->getAuthProviderLinkHref(),
            $this->getAuthProviderLink()
        );
    }

}