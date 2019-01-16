.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _templatingTemplateSelector:

Template Selector
=================

It is possible to provide selectable template layouts to the editors, eg. a "list layout" and a "tile layout", or
different searchbox layouts.

.. image:: ../Images/Templating/template-layout-selector.png

Usage
-----

Register your new templateLayout in the *Page TSConfig*.
It will then appear in the plugin and the editor will be able to select ist.

.. code-block:: none

    TCEFORM {
        tt_content {
            pi_flexform.ke_search_pi1.view.templateLayout.addItems {
                20 = Custom search box 1
            }
        }
    }

Register your own template paths, see :ref:`custom-templates`.

Add the new layout inside a condition which checks for the setting "conf.templateLayout".

.. code-block:: none

    <f:layout name="General"/>
    <f:section name="content">
        <f:if condition="{conf.templateLayout}">
            <f:if condition="{conf.templateLayout} == 10">
                <f:render section="defaultLayout" arguments="{_all}"/>
            </f:if>
            <f:if condition="{conf.templateLayout} == 20">
                <f:render section="customLayout" arguments="{_all}"/>
            </f:if>
        </f:if>
    </f:section>

    <f:section name="defaultLayout">
        ... default template code
    </f:section>

    <f:section name="customLayout">
        ... custom template code
    </f:section>

