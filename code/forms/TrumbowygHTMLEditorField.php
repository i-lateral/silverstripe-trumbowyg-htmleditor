<?php
/**
 * A simple WYSIWYG HTML editor field that can be used to add basic HTML
 * editing capabilities to a Silverstripe site.
 * 
 * At the moment this is designed to only be used in a front end form,
 * it would be nice to add support for the CMS however, and this will be
 * a future development.
 * 
 * Some of this code has been taken from the Silverstripe HTMLEditor
 * field
 *
 * @package Trumbowyg HTMLEditorField
 */
class TrumbowygHTMLEditorField extends TextareaField
{

    /**
     * @config
     * @var bool Should we check the valid_elements (& extended_valid_elements) rules from HtmlEditorConfig server side?
     */
    private static $sanitise_server_side = false;
    
    /**
     * Default buttons we will use
     * 
     * @var array
     * @config
     */
    private static $default_buttons = array(
        'btnGrp-design',
        "|",
        'btnGrp-lists'
    );

    protected $rows = 30;
    
    protected $buttons = array();
    
    /**
     * Get all the current buttons
     * 
     * @return array
     */
    public function getButtons()
    {
        if ($this->buttons && is_array($this->buttons)) {
            return $this->buttons;
        } else {
            return $this->config()->default_buttons;
        }
    }
    
    /**
     * Set our array of buttons
     * 
     * @param 
     * @return Object
     */
    public function setButtons($buttons)
    {
        $this->buttons = $buttons;
        return $this;
    }
    
    /**
     * Set our array of buttons
     * 
     * @param 
     * @return Object
     */
    public function addButton($button)
    {
        $buttons = $this->buttons;
        
        // If buttons isn't an array, set it
        if (!is_array($buttons)) {
            $buttons = array();
        }
            
        $buttons[] = $button;
        
        $this->buttons = $buttons;
        
        return $this;
    }
    
    /**
     * Get all the current buttons rendered as a string for JS
     * 
     * @return array
     */
    public function getButtonsJS()
    {
        $buttons = $this->getButtons();
        $str = "";
        
        for ($x = 0; $x < count($buttons); $x++) {
            $str .= "'" . $buttons[$x] . "'";
            
            if ($x < (count($buttons) - 1)) {
                $str .= ",";
            }
        }
        
        return $str;
    }
    
    /**
     * @see TextareaField::__construct()
     */
    public function __construct($name, $title = null, $value = '')
    {
        parent::__construct($name, $title, $value);
        
        // Add CSS and JS requirements
        Requirements::css("trumbowyg-htmleditor/thirdparty/trumbowyg/ui/trumbowyg.min.css");
        Requirements::css("trumbowyg-htmleditor/css/TrumbowygHtmlEditorField.css");
        Requirements::javascript("framework/thirdparty/jquery/jquery.js");
        Requirements::javascript("trumbowyg-htmleditor/thirdparty/trumbowyg/trumbowyg.min.js");
    }
    
    /**
     * @return string
     */
    public function Field($properties = array())
    {

        // Before rendering our field, require our custom script
        Requirements::javascriptTemplate(
            "trumbowyg-htmleditor/javascript/trumbowyg-init.js",
            array(
                'ID' => $this->ID(),
                "Buttons" => $this->getButtonsJS()
            )
        );

        return parent::Field($properties);
    }
    
    public function saveInto(DataObjectInterface $record)
    {
        if ($record->hasField($this->name) && $record->escapeTypeForField($this->name) != 'xml') {
            throw new Exception(
                'HtmlEditorField->saveInto(): This field should save into a HTMLText or HTMLVarchar field.'
            );
        }
        
        $htmlValue = Injector::inst()->create('HTMLValue', $this->value);

        // Sanitise if requested
        if ($this->config()->sanitise_server_side) {
            $santiser = Injector::inst()->create('HtmlEditorSanitiser', HtmlEditorConfig::get_active());
            $santiser->sanitise($htmlValue);
        }

        // optionally manipulate the HTML after a TinyMCE edit and prior to a save
        $this->extend('processHTML', $htmlValue);

        // Store into record
        $record->{$this->name} = $htmlValue->getContent();
    }

    /**
     * @return HtmlEditorField_Readonly
     */
    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy('TrumbowygHTMLEditorField_Readonly');
        $field->dontEscape = true;
        
        return $field;
    }
    
    public function performDisabledTransformation()
    {
        return $this->performReadonlyTransformation();
    }
}

/**
 * Readonly version of an {@link HTMLEditorField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class TrumbowygHTMLEditorField_Readonly extends ReadonlyField
{
    public function Field($properties = array())
    {
        $valforInput = $this->value ? Convert::raw2att($this->value) : "";
        return "<span class=\"readonly typography\" id=\"" . $this->id() . "\">"
            . ($this->value && $this->value != '<p></p>' ? $this->value : '<i>(not set)</i>')
            . "</span><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
    }
    public function Type()
    {
        return 'htmleditorfield readonly';
    }
}
