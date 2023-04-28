<?php


namespace App\Model\Feed;


class SimpleXMLExtended extends \SimpleXMLElement {

    public function addCData($cdata_text) {
        $node = dom_import_simplexml($this);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }

    public function addChildWithCDATA( $name, $value = NULL )
    {
        $new_child = $this->addChild( $name );
        if ( $new_child !== NULL ) {
            $node = dom_import_simplexml( $new_child );
            $no   = $node->ownerDocument;
            $node->appendChild( $no->createCDATASection( $value ) );
        }
        return $new_child;
    }

}