<?php
namespace TypeRocket\Elements\Fields;

use \TypeRocket\Html\Generator;
use \TypeRocket\Core\Config;
use \TypeRocket\Elements\Traits\MaxlengthTrait;

class Editor extends Textarea implements ScriptField
{
    use MaxlengthTrait;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'editor' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        $paths = Config::getPaths();
        $assets = $paths['urls']['assets'];
        wp_enqueue_media();
        wp_enqueue_script( 'typerocket-editor', $assets . '/typerocket/js/redactor.min.js', ['jquery'], '1.0', true );
    }

    /**
     * Covert Editor to HTML string
     */
    public function getString()
    {
        $generator = new Generator();
        $this->setAttribute('name', $this->getNameAttributeString());
        $value = $this->getValue();
        $this->appendStringToAttribute('class', ' typerocket-editor ');
        $value = $this->sanitize($value, 'editor' );
        $max = $this->getMaxlength( wp_strip_all_tags($value), $this->getAttribute('maxlength'));

        return $generator->newElement( 'textarea', $this->getAttributes(), esc_attr($value) )->getString() . $max;
    }

}
