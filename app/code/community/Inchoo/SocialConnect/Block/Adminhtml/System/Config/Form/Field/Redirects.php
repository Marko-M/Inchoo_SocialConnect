<?php

abstract class Inchoo_SocialConnect_Block_Adminhtml_System_Config_Form_Field_Redirects
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function getAuthProvider()
    {
        return '';
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        return sprintf(
            '<pre>%ssocialconnect/%s/connect/</pre>',
            Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            $this->getAuthProvider()
        );
    }

}