<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Class ilPCMap
 * Map content object (see ILIAS DTD)
 * @author Alexander Killing <killing@leifos.de>
 */
class ilPCMap extends ilPageContent
{
    public function init(): void
    {
        $this->setType("map");
    }

    public function create(
        ilPageObject $a_pg_obj,
        string $a_hier_id,
        string $a_pc_id = ""
    ): void {
        $this->createInitialChildNode(
            $a_hier_id,
            $a_pc_id,
            "Map",
            ["Latitude" => "0","Longitude" => "0","Zoom" => "3"]
        );
    }

    public function setLatitude(?float $a_lat = null): void
    {
        if (!is_null($a_lat)) {
            $this->getChildNode()->setAttribute("Latitude", (string) $a_lat);
        } else {
            if ($this->getChildNode()->hasAttribute("Latitude")) {
                $this->getChildNode()->removeAttribute("Latitude");
            }
        }
    }

    public function getLatitude(): ?float
    {
        if (is_object($this->getChildNode())) {
            return (float) $this->getChildNode()->getAttribute("Latitude");
        }
        return null;
    }

    public function setLongitude(?float $a_long = null): void
    {
        if (!is_null($a_long)) {
            $this->getChildNode()->setAttribute("Longitude", $a_long);
        } else {
            if ($this->getChildNode()->hasAttribute("Longitude")) {
                $this->getChildNode()->removeAttribute("Longitude");
            }
        }
    }

    public function getLongitude(): ?float
    {
        if (is_object($this->getChildNode())) {
            return (float) $this->getChildNode()->getAttribute("Longitude");
        }
        return null;
    }

    public function setZoom(?int $a_zoom): void
    {
        //if (!empty($a_zoom)) {
        $this->getChildNode()->setAttribute("Zoom", (int) $a_zoom);
        /*} else {
            if ($this->map_node->has_attribute("Zoom")) {
                $this->map_node->remove_attribute("Zoom");
            }
        }*/
    }

    public function getZoom(): ?int
    {
        if (is_object($this->getChildNode())) {
            return (int) $this->getChildNode()->getAttribute("Zoom");
        }
        return null;
    }

    public function setLayout(
        ?int $a_width,
        ?int $a_height,
        string $a_horizontal_align
    ): void {
        if (is_object($this->getChildNode())) {
            $this->dom_util->setFirstOptionalElement(
                $this->getChildNode(),
                "Layout",
                array("MapCaption"),
                "",
                array("Width" => (string) $a_width,
                    "Height" => (string) $a_height, "HorizontalAlign" => $a_horizontal_align)
            );
        }
    }

    public function getWidth(): ?int
    {
        if (is_object($this->getChildNode())) {
            foreach ($this->getChildNode()->childNodes as $child) {
                if ($child->nodeName == "Layout") {
                    $w = $child->getAttribute("Width")
                        ? (int) $child->getAttribute("Width")
                        : null;
                    return $w;
                }
            }
        }
        return null;
    }

    public function getHeight(): ?int
    {
        if (is_object($this->getChildNode())) {
            foreach ($this->getChildNode()->childNodes as $child) {
                if ($child->nodeName == "Layout") {
                    $h = $child->getAttribute("Height")
                        ? (int) $child->getAttribute("Height")
                        : null;
                    return $h;
                }
            }
        }
        return null;
    }

    public function getHorizontalAlign(): string
    {
        if (is_object($this->getChildNode())) {
            foreach ($this->getChildNode()->childNodes as $child) {
                if ($child->nodeName == "Layout") {
                    return $child->getAttribute("HorizontalAlign");
                }
            }
        }
        return "";
    }

    public function setCaption(string $a_caption): void
    {
        if (is_object($this->getChildNode())) {
            $this->dom_util->setFirstOptionalElement(
                $this->getChildNode(),
                "MapCaption",
                array(),
                $a_caption,
                array()
            );
        }
    }

    public function getCaption(): string
    {
        if (is_object($this->getChildNode())) {
            foreach ($this->getChildNode()->childNodes as $child) {
                if ($child->nodeName == "MapCaption") {
                    return $this->dom_util->getContent($child);
                }
            }
        }
        return "";
    }

    public static function handleCaptionInput(
        string $a_text
    ): string {
        $a_text = str_replace(chr(13) . chr(10), "<br />", $a_text);
        $a_text = str_replace(chr(13), "<br />", $a_text);
        $a_text = str_replace(chr(10), "<br />", $a_text);

        return $a_text;
    }

    public static function handleCaptionFormOutput(
        string $a_text
    ): string {
        $a_text = str_replace("<br />", "\n", $a_text);
        $a_text = str_replace("<br/>", "\n", $a_text);

        return $a_text;
    }

    public function modifyPageContentPostXsl(
        string $a_output,
        string $a_mode,
        bool $a_abstract_only = false
    ): string {
        $end = 0;
        $start = strpos($a_output, "[[[[[Map;");
        if (is_int($start)) {
            $end = strpos($a_output, "]]]]]", $start);
        }
        $i = 1;
        while ($end > 0) {
            $param = substr($a_output, $start + 9, $end - $start - 9);

            $param = explode(";", $param);
            if (is_numeric($param[0]) && is_numeric($param[1]) && is_numeric($param[2])) {
                $map_gui = ilMapUtil::getMapGUI();
                $map_gui->setMapId("map_" . $i)
                        ->setLatitude($param[0])
                        ->setLongitude($param[1])
                        ->setZoom($param[2])
                        ->setWidth($param[3] . "px")
                        ->setHeight($param[4] . "px")
                        ->setEnableTypeControl(true)
                        ->setEnableNavigationControl(true)
                        ->setEnableCentralMarker(true);
                $h2 = substr($a_output, 0, $start) .
                    $map_gui->getHtml() .
                    substr($a_output, $end + 5);
                $a_output = $h2;
                $i++;
            }
            $start = strpos($a_output, "[[[[[Map;", $start + 5);
            $end = 0;
            if (is_int($start)) {
                $end = strpos($a_output, "]]]]]", $start);
            }
        }

        return $a_output;
    }
}
