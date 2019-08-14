<?php
class Emagicone_Mobassistantconnector_Block_Adminhtml_System_Config_Form_Qrconfig extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mobassistantconnector/jsinit.phtml');
    }
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

}