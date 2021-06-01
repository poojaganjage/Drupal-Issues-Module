<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/contrib/betterlogin/templates/page--user--login.html.twig */
class __TwigTemplate_c7c1f4dddd47ec2a8d8f76bf538b11b6e067157eea4a66f5c6eb8f10cf3eee85 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["if" => 49];
        $filters = ["escape" => 29, "t" => 46];
        $functions = ["url" => 28, "path" => 46];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape', 't'],
                ['url', 'path']
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 24
        echo "
<div id=\"auth_box\" class=\"login\">
  <div id=\"top_part\">
    <h1 id=\"the_logo\">
      <a href=\"";
        // line 28
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar($this->env->getExtension('Drupal\Core\Template\TwigExtension')->getUrl("<front>"));
        echo "\">
        <img src=\"";
        // line 29
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["logo"] ?? null)), "html", null, true);
        echo "\" alt=\"";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["site_name"] ?? null)), "html", null, true);
        echo "\" />
      </a>
    </h1>
  </div>

  <div id=\"middle_part\">
    <h2 class=\"title\">";
        // line 35
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["title"] ?? null)), "html", null, true);
        echo "</h2>

    ";
        // line 37
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "highlighted", [])), "html", null, true);
        echo "

    ";
        // line 39
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["messages"] ?? null)), "html", null, true);
        echo "

    ";
        // line 41
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "content", [])), "html", null, true);
        echo "
  </div>

  <div id=\"bottom_part\">
    <div class=\"password_link\">
      <a href=\"";
        // line 46
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar($this->env->getExtension('Drupal\Core\Template\TwigExtension')->getPath("user.pass"));
        echo "\">";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Forgot your password?"));
        echo "</a>
    </div>

    ";
        // line 49
        if (($context["register_url"] ?? null)) {
            // line 50
            echo "      <div class=\"register_link\">
        <a href=\"";
            // line 51
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["register_url"] ?? null)), "html", null, true);
            echo "\">";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Register a new account"));
            echo "</a>
      </div>
    ";
        }
        // line 54
        echo "
    <div class=\"back_link\">
      <a href=\"";
        // line 56
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar($this->env->getExtension('Drupal\Core\Template\TwigExtension')->getUrl("<front>"));
        echo "\">&larr; ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Back"));
        echo " ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["site_name"] ?? null)), "html", null, true);
        echo "</a>
    </div>
  </div>
</div>
";
    }

    public function getTemplateName()
    {
        return "modules/contrib/betterlogin/templates/page--user--login.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  124 => 56,  120 => 54,  112 => 51,  109 => 50,  107 => 49,  99 => 46,  91 => 41,  86 => 39,  81 => 37,  76 => 35,  65 => 29,  61 => 28,  55 => 24,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{#
/**
 * @file
 * Better Login theme implementation to display a login page.
 *
 *
 * Available variables:
 *
 * General utility variables:
 * - url: The base URL path of the Drupal website.
 * - logo: Logo path of Drupal website.
 * - site_name: Site name of Drupal website.
 * - title: Title of a page.
 * - register_url: A registration url of Drupal website.
 *
 *
 * Regions:
 * - page.highlighted: Items for the highlighted region.
 * - page.content: The main content of the current page.
 *
 * @see betterlogin_preprocess_betterlogin()
 */
#}

<div id=\"auth_box\" class=\"login\">
  <div id=\"top_part\">
    <h1 id=\"the_logo\">
      <a href=\"{{ url('<front>') }}\">
        <img src=\"{{ logo }}\" alt=\"{{ site_name }}\" />
      </a>
    </h1>
  </div>

  <div id=\"middle_part\">
    <h2 class=\"title\">{{ title }}</h2>

    {{ page.highlighted }}

    {{ messages }}

    {{ page.content }}
  </div>

  <div id=\"bottom_part\">
    <div class=\"password_link\">
      <a href=\"{{ path('user.pass') }}\">{{ 'Forgot your password?'|t }}</a>
    </div>

    {% if register_url %}
      <div class=\"register_link\">
        <a href=\"{{ register_url }}\">{{ 'Register a new account'|t }}</a>
      </div>
    {% endif %}

    <div class=\"back_link\">
      <a href=\"{{ url('<front>') }}\">&larr; {{ 'Back'|t }} {{ site_name }}</a>
    </div>
  </div>
</div>
", "modules/contrib/betterlogin/templates/page--user--login.html.twig", "C:\\xampp\\htdocs\\drupal\\modules\\contrib\\betterlogin\\templates\\page--user--login.html.twig");
    }
}
