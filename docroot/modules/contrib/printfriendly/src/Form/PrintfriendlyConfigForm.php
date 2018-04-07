<?php

/**

 * @file

 * Contains \Drupal\printfriendly\Form\printfriendlyConfigForm.

 */


namespace Drupal\printfriendly\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PrintfriendlyConfigForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'printfriendly_config_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('printfriendly.settings');

	$form = parent::buildForm($form, $form_state);

	//$form['#attached']['#attached']['library'][] = 'printfriendly/printfriendly-libraries';

  $form['printfriendly_notification'] = array(
    '#markup' => '<div>
        <h2>Does your website use these technologies?</h2>
        <ul>
            <li>- Password protected websites (paywall or intranet)</li>
            <li>- JavaScript to display content (Angular/React applications)</li>
        </ul>
        <p>If yes, you need to <a href="https://www.printfriendly.com/button/pro">purchase a PrintFriendly Pro subscription</a> for the plugin to work properly on your website (<a href="http://blog.printfriendly.com/2017/11/printfriendly-pdf-plugin-is-changing.html">learn why</a>).</p>
        <p>If you are an existing Pro customer, no further action is required</p>
      </div>'
    ,
  );

	$form['printfriendly_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Content Types'),
      '#description' => t('Configure where the printfriendly button should appear.'),
      '#options' => node_type_get_names(),
      '#default_value' => $config->get('printfriendly_types', array()),
	);

    $default_display = '';
    if(!empty($config->get('printfriendly_display'))) $default_display = array_filter($config->get('printfriendly_display'));
	$form['printfriendly_display'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Button display'),
      '#description' => t('Select content displays that the button should appear.'),
      '#options' => array(
        'teaser' => t('Teaser'),
        'full' => t('Full content page'),
      ),
      '#default_value' => $default_display,
	);

    // Add more features here
    $form['printfriendly_features'] = array(
      '#type' => 'fieldset',
      '#title' => t('Features'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

	$img_path = drupal_get_path('module', 'printfriendly') . '/images';
    $results = file_scan_directory($img_path, '/^.*\.(gif|png|jpg|GIF|PNG|JPG)$/');
    $options = array();
    foreach ($results as $image) {
      $options[$image->filename] = '<img src="' . file_create_url($image->uri) . '" />';
    }
    ksort($options);

	// set custom button image options.
    $options['custom-button-img-url'] = '';
    
	$default_print_icon = '';
    if($config->get('printfriendly_image') !== null) $default_print_icon = $config->get('printfriendly_image'); else $default_print_icon = 'button-print-grnw20.png';
    $form['printfriendly_features']['printfriendly_image'] = array(
      '#type' => 'radios',
      '#title' => t('Choose button'),
      '#options' => $options,
	  '#default_value' => $default_print_icon,      
    );

	$form['printfriendly_features']['custom_button_img_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom button image URL'),
      '#description' => t('Put full path of an image http://devt.drupalchamp.org/sites/default/files/drupal-logo.png'),
      '#default_value' => $config->get('custom_button_img_url', ''),
   );

    $form['printfriendly_features']['printfriendly_page_header'] = array(
      '#type' => 'select',
      '#title' => t('Page header'),
      '#options' => array(
        'default_logo' => 'My Website Icon',
        'custom_logo' => 'Upload an Image',
      ),
	  '#default_value' => $config->get('printfriendly_page_header', 'default_logo'),
    );

    $form['printfriendly_features']['printfriendly_page_custom_header'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter URL'),
      '#description' => t('Put full path of the file like http://devt.drupalchamp.org/sites/default/files/drupal-logo.png'),
      '#states' => array(
        'invisible' => array(
          ':input[name="printfriendly_page_header"]' => array(
            array('value' => t('default_logo')),
          ),
        ),
      ),
      '#default_value' => $config->get('printfriendly_page_custom_header', ''),
    );

    $form['printfriendly_features']['printfriendly_tagline'] = array(
      '#type' => 'textfield',
      '#title' => t('Header tagline'),
      '#default_value' => $config->get('printfriendly_tagline', ''),
      '#description' => t('Add a specific tagline to the header.'),
      '#states' => array(
        'invisible' => array(
          ':input[name="printfriendly_page_header"]' => array(
            array('value' => t('default_logo')),
          ),
        ),
      ),
    );

    $form['printfriendly_features']['printfriendly_click_delete'] = array(
      '#type' => 'select',
      '#title' => t('Click-to-delete'),
      '#options' => array(
        '0' => 'Allow',
        '1' => 'Not Allow',
      ),
	  '#default_value' => $config->get('printfriendly_click_delete', '0'),
    );

    $form['printfriendly_features']['printfriendly_images'] = array(
      '#type' => 'select',
      '#title' => t('Images'),
      '#options' => array(
        '0' => 'Include',
        '1' => 'Exclude',
       ),
      '#default_value' => $config->get('printfriendly_images', '0'),
    );
  
    $form['printfriendly_features']['printfriendly_image_style'] = array(
      '#type' => 'select',
      '#title' => t('Image style'),
      '#options' => array(
        'right' => 'Align Right',
        'left' => 'Align Left',
        'none' => 'Align None',
        'block' => 'Center/Block',
       ),
      '#default_value' => $config->get('printfriendly_image_style', 'right'),
    );

    $form['printfriendly_features']['printfriendly_email'] = array(
      '#type' => 'select',
      '#title' => t('Email'),
      '#options' => array(
        '0' => 'Allow',
        '1' => 'Not Allow',
       ),
      '#default_value' => $config->get('printfriendly_email', '0'),
    );

    $form['printfriendly_features']['printfriendly_pdf'] = array(
      '#type' => 'select',
      '#title' => t('PDF'),
      '#options' => array(
        '0' => 'Allow',
        '1' => 'Not Allow',
       ),
      '#default_value' => $config->get('printfriendly_pdf', '0'),
    );

    $form['printfriendly_features']['printfriendly_print'] = array(
      '#type' => 'select',
      '#title' => t('Print'),
      '#options' => array(
        '0' => 'Allow',
        '1' => 'Not Allow',
       ),
      '#default_value' => $config->get('printfriendly_print', '0'),
    );

    $form['printfriendly_features']['printfriendly_custom_css'] = array(
      '#type' => 'textfield',
      '#description' => t('Put full path of the file like http://devt.drupalchamp.org/sites/default/files/printfriendly.css'),
      '#title' => t('Custom css url'),
      '#default_value' => $config->get('printfriendly_custom_css', ''),
    );

	$form['support-link'] = array(
      '#markup' => 'Need help or have suggestions? <a href="mailto:support@printfriendly.com">Support@PrintFriendly.com</a>',
	  '#weight' => 1000,
    );
  
    return $form;
  }
 

  /**
   * {@inheritdoc}
   */
 
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('printfriendly.settings');

    $config->set('printfriendly_types', $form_state->getValue('printfriendly_types'));
    $config->set('printfriendly_display', $form_state->getValue('printfriendly_display'));
    $config->set('printfriendly_page_header', $form_state->getValue('printfriendly_page_header'));
    $config->set('printfriendly_page_custom_header', $form_state->getValue('printfriendly_page_custom_header'));
    $config->set('printfriendly_tagline', $form_state->getValue('printfriendly_tagline'));
    if($form_state->getValue('printfriendly_page_header') == 'default_logo'){
      $config->set('printfriendly_page_custom_header', '');
      $config->set('printfriendly_tagline', '');
    }
    $config->set('printfriendly_click_delete', $form_state->getValue('printfriendly_click_delete'));
    $config->set('printfriendly_images', $form_state->getValue('printfriendly_images'));
    $config->set('printfriendly_image_style', $form_state->getValue('printfriendly_image_style'));
    $config->set('printfriendly_email', $form_state->getValue('printfriendly_email'));
    $config->set('printfriendly_pdf', $form_state->getValue('printfriendly_pdf'));
    $config->set('printfriendly_print', $form_state->getValue('printfriendly_print'));
    $config->set('printfriendly_custom_css', $form_state->getValue('printfriendly_custom_css'));
    $config->set('printfriendly_image', $form_state->getValue('printfriendly_image'));

	if($form_state->getValue('printfriendly_image') == 'custom-button-img-url'){
      $config->set('custom_button_img_url', $form_state->getValue('custom_button_img_url'));
    }else{
      $config->set('custom_button_img_url', '');
    }
	 
    $config->save();

    return parent::submitForm($form, $form_state);
 
  }
 
  /**
   * {@inheritdoc}
   */
 
  public function getEditableConfigNames() {
    return ['printfriendly.settings'];
  }

}
