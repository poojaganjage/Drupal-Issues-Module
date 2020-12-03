<?php

namespace Drupal\filter_script_tags\Plugin\Filter;

 use Drupal\Core\Form\FormStateInterface;
 use Drupal\filter\FilterProcessResult;
 use Drupal\filter\Plugin\FilterBase;
 
 /** 
  * Provides a filter to filter not allowed script tags.
  *
  * @Filter(
  *   id = "filter_script_tags",
  *   title = @Translation("Filter Script Tags"),
  *   description = @Translation("Remove external script tags from displaying
  *   according to hostnames. This is especially useful for sites that allow
  *   full HTML inputs."),
  *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
  *   settings = {
  *     "filter_script_tags_allowed_domains" = "",
  *   }
  * )
  */

class FilterScriptTags extends FilterBase {

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        
        $form['filter_script_tags_allowed_domains'] = [
          '#type' => 'textarea',
          '#title' => t('Allowed domain list'),
          '#description' => t("Only scripts from these domains are allowed.(One domain per line).<br>Examples:<br>example.com<br>*.example.com"),
          '#default_value' => isset($this->settings['filter_script_tags_allowed_domains']) ? $this->settings['filter_script_tags_allowed_domains'] : '',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function tips($long = FALSE) {
      
      return $this->t('Removes unsafe external script tags and removes empty tags from appearing on the page');
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode) {

        $text = $this->scriptFilterScript($text, $this->scriptFilterMapRegex($this->settings['filter_script_tags_allowed_domains']));
        //** Return if string not given or empty.
        if (!is_string($text) || trim($text) === '') {
          return new FilterProcessResult($text);
        }
     
        //** Recursive empty HTML tags.
        $text = preg_replace(
        //** Pattern to match empty tags.
          '/<([^<\/>]*)>([\s]*?|(?R))<\/\1>/imsU',
          //** Replace with nothing.
          '',
          //** Source string
          $text
        );


        return new FilterProcessResult($text);
    }

    /**
     * Helper function to scriptfilter_process_callback().
     *
     * @param string $allowed_domains
     *   String of domains (with wildcard) to work with.
     *
     * @return array
     *   Array of regular expression to match the domains (with wildcard).
     */
    private function scriptFilterMapRegex($allowed_domains) {
        
        $allowed_domains = trim($allowed_domains);
        $allowed_domain_list = !empty($allowed_domains) ? preg_split("/([ \t]*(\n|\r\n)+[ \t]*)+/", $allowed_domains) : [];

        return array_map(function ($host) {
          $host = preg_quote($host, NULL);
          $host = preg_replace('/\\\\\*/', '.*?', $host);
          $host = preg_replace('#/#', '\/', $host);
          return '/' . $host . '/';
        }, $allowed_domain_list);
    }

    /**
     * Filter script.
     *
     * Removes all external scripts with src host not within whitelist.
     * Removes anything within <script> tags.
     * Removes anything that is not a <script> tag.
     *
     * @param string $string
     *   HTML content to filter from.
     * @param array $allowed_domain_list_regex
     *   Array of regular expressions to match whitelist domains.
     *
     * @return string
     *   Sanitized HTML
     */
    private function scriptFilterScript($string, array $allowed_domain_list_regex = []) {

        // Load the whole text string into a DOM object so we can properly
        // extract the script src. Using regex is way too messy and difficult
        // to ensure consistency.
        $filtered_list = [];
        $send_new_html = FALSE;

        $dom = $this->load($string);
        $body = $dom->getElementsByTagName('body')->item(0);
        $this->sanitizeRecursively(
          $body,
          $filtered_list,
          $allowed_domain_list_regex,
          $send_new_html
        );

        // Filter detected as not-allowed scripts tag
        foreach ($filtered_list as $to_remove) {
          $to_remove->parentNode->removeChild($to_remove);
        }
     
        // If we have modified $html_input, we need to return it.
        if ($send_new_html && $body !== NULL) {
          $html = '';
          foreach ($body->childNodes as $node) {
            $html .= $dom->saveXML($node);
          }
          return $html;
        }
        return $string;
    }

    protected function sanitizeRecursively($root_element, &$to_remove, &$allowed_domain_list_regex, &$send_new_html) {
        foreach ($root_element->childNodes as $element) {

            // Process child elements first.
            if (!empty($element->childNodes)) {
                $this->sanitizeRecursively($element, $to_remove, $allowed_domain_list_regex, $send_new_html);
            }

            // Remove anything that is not a script tag.
            if ($element->nodeName === "script" && $element->hasAttribute('src')) {
            // Remove the script if its src is not valid or is not whitelisted.
                $src = $element->getAttribute('src');
                $host = parse_url($src, PHP_URL_HOST);
                if (empty($host) || !$this->scriptFilterArrayMatch($host, $allowed_domain_list_regex)) {
                  $to_remove[] = $element;
                  $send_new_html = TRUE;
                }
         
                // Remove any code (or anything at all) within the script tag.
                while ($element->firstChild) {
                  $element->removeChild($element->firstChild);
                  $send_new_html = TRUE;
                }
            }
        }
    }


    /**
     * Parses an HTML snippet and returns it as a DOM object.
     *
     * This function loads the body part of a partial (X)HTML document and returns
     * a full \DOMDocument object that represents this document.
     *
     * Use \Drupal\Component\Utility\Html::serialize() to serialize this
     * \DOMDocument back to a string.
     *
     * @param string $html
     *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
     *   import.
     *
     * @return \DOMDocument
     *   A \DOMDocument that represents the loaded (X)HTML snippet.
     */
    public function load($html) {
    $document = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
<body>!html</body>
</html>
EOD;
        // PHP's \DOMDocument serialization adds extra whitespace when the markup
        // of the wrapping document contains newlines, so ensure we remove all
        // newlines before injecting the actual HTML body to be processed.
        $document = strtr($document, ["\n" => '', '!html' => $html]);

        $dom = new \DOMDocument();
        // Ignore warnings during HTML soup loading.
        // Set flag to indicate whether we have modified $html_input and therefore
        // need to return it.
        @$dom->loadHTML($document, LIBXML_HTML_NOIMPLIED);

        return $dom;
    }

    /**
     * Helper function to scriptFilterScript().
     *
     * @param string $host
     *   Domain name from script tag.
     * @param array $regex_list
     *   Array of allowed domain name list.
     *
     * @return bool
     *   Is matched.
     */
    private function scriptFilterArrayMatch($host, array $regex_list) {
        foreach ($regex_list as $regex) {
          if (preg_match($regex, $host)) {
            return TRUE;
          }
        }
        return FALSE;
    }
}