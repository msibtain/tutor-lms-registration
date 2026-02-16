<?php
/**
 * Handler subscription related e-mail
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Controllers;

use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Models\OrderModel;
use TUTOR_EMAIL\EmailData;
use TUTOR_EMAIL\EmailNotification;
use TUTOR_EMAIL\EmailPlaceholder;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;

/**
 * EmailController Class.
 *
 * @since 3.0.0
 */
class EmailController {
	/**
	 * Subscription model.
	 *
	 * @var SubscriptionModel
	 */
	private $subscription_model;

	/**
	 * Order model.
	 *
	 * @var OrderModel
	 */
	private $order_model;

	/**
	 * Order controller instance.
	 *
	 * @var OrderController
	 */
	private $order_ctrl;

	/**
	 * Plan model
	 *
	 * @var PlanModel
	 */
	private $plan_model;

	/**
	 * Instance of email notification.
	 *
	 * @since 3.0.0
	 *
	 * @var \TUTOR_EMAIL\EmailNotification
	 */
	private $email_notification;

	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 *
	 * @param bool $register_hooks whether to register hooks or not.
	 */
	public function __construct( $register_hooks = true ) {
		$this->subscription_model = new SubscriptionModel();
		$this->order_model        = new OrderModel();
		$this->order_ctrl         = new OrderController();
		$this->plan_model         = new PlanModel();
		$this->email_notification = new EmailNotification( false );

		if ( ! $register_hooks ) {
			return;
		}

		add_filter( 'tutor_pro/email/list', array( $this, 'subscription_email_list' ) );
		add_filter( 'tutor_pro_email_placeholders', array( $this, 'add_placeholders' ) );

		/**
		 * Handle e-mail events
		 */
		add_action( 'tutor_subscription_activated', array( $this, 'to_student_subscription_activated' ) );
		add_action( 'tutor_subscription_renewed', array( $this, 'to_student_subscription_renewed' ) );
		add_action( 'tutor_subscription_hold', array( $this, 'to_student_subscription_hold' ) );
		add_action( 'tutor_subscription_cancelled', array( $this, 'to_student_subscription_cancelled' ) );
		add_action( 'tutor_subscription_expired', array( $this, 'to_student_subscription_expired' ) );
	}

	/**
	 * Add subscription related placeholders.
	 *
	 * @since 3.0.0
	 *
	 * @param array $arr list.
	 *
	 * @return array
	 */
	public function add_placeholders( $arr ) {
		$arr['plan_name'] = array(
			'placeholder' => '{plan_name}',
			'label'       => __( 'Plan Name', 'tutor-pro' ),
			'test_data'   => __( 'Dummy Plan', 'tutor-pro' ),
		);

		$arr['expiry_date'] = array(
			'placeholder' => '{expiry_date}',
			'label'       => __( 'Expiry Date', 'tutor-pro' ),
			'test_data'   => gmdate( 'Y-m-d', strtotime( '+1 month' ) ),
		);

		return $arr;
	}

	/**
	 * Add subscription email list.
	 *
	 * @since 3.0.0
	 *
	 * @param array $list email list.
	 *
	 * @return array
	 */
	public function subscription_email_list( $list ) {
		$list[ EmailNotification::TO_STUDENTS ]['subscription_activated'] = array(
			'label'        => __( 'Subscription Activated', 'tutor-pro' ),
			'default'      => 'on',
			'template'     => 'to_student_subscription_activated',
			'tooltip'      => 'Email sent to student when new subscription get activated',
			'subject'      => __( 'Congratulations! Your Subscription is Now Active!', 'tutor-pro' ),
			'heading'      => __( 'Subscription activated!', 'tutor-pro' ),
			'message'      => wp_json_encode( 'We\'re excited to inform you that your subscription to <strong>{plan_name}</strong> is now active! You can now access the course materials, resources, and updates that come with this subscription.<br><br>Thank you for choosing <strong>{site_name}</strong> and trusting us to be part of your learning experience' ),
			'footer_text'  => __( 'This is an automated message. Please do not reply', 'tutor-pro' ),
			'placeholders' => EmailPlaceholder::only( array( 'site_url', 'site_name', 'plan_name' ) ),
		);

		$list[ EmailNotification::TO_STUDENTS ]['subscription_hold'] = array(
			'label'        => __( 'Subscription on Hold', 'tutor-pro' ),
			'default'      => 'on',
			'template'     => 'to_student_subscription_hold',
			'tooltip'      => 'Email sent to student when subscription on hold',
			'subject'      => __( 'Your Subscription is Currently On Hold', 'tutor-pro' ),
			'heading'      => __( 'Subscription on hold', 'tutor-pro' ),
			'message'      => wp_json_encode( 'We wanted to inform you that your subscription to <strong>{plan_name}</strong> is currently on hold. This could be due to payment issues or other reasons.<br></br>We apologize for any inconvenience and look forward to serving you again soon. If you encounter any issues with purchasing the subscription, please reach out to us' ),
			'footer_text'  => __( 'This is an automated message. Please do not reply', 'tutor-pro' ),
			'placeholders' => EmailPlaceholder::only( array( 'site_url', 'site_name', 'plan_name' ) ),
		);

		$list[ EmailNotification::TO_STUDENTS ]['subscription_renewed'] = array(
			'label'        => __( 'Subscription Renewed', 'tutor-pro' ),
			'default'      => 'on',
			'template'     => 'to_student_subscription_renewed',
			'tooltip'      => 'Email sent to student when subscription is renewed',
			'subject'      => __( 'Your Subscription Has Been Renewed!', 'tutor-pro' ),
			'heading'      => __( 'Subscription renewed!', 'tutor-pro' ),
			'message'      => wp_json_encode( 'Your subscription to <strong>{plan_name}</strong> has been successfully renewed! <br><br> The updated subscription will be valid for <strong>{expiry_date}</strong>. You can now continue to access all the features and resources available on your subscription plan. <br><br>Thank you for your continued trust in <strong>{site_name}</strong>. If you have any queries or require assistance, please contact us.' ),
			'footer_text'  => __( 'This is an automated message. Please do not reply', 'tutor-pro' ),
			'placeholders' => EmailPlaceholder::only( array( 'site_url', 'site_name', 'plan_name', 'expiry_date' ) ),
		);

		$list[ EmailNotification::TO_STUDENTS ]['subscription_expired'] = array(
			'label'        => __( 'Subscription Expired', 'tutor-pro' ),
			'default'      => 'on',
			'template'     => 'to_student_subscription_expired',
			'tooltip'      => 'Email sent to student when subscription is expired',
			'subject'      => __( 'Your Subscription Has Expired', 'tutor-pro' ),
			'heading'      => __( 'Subscription expired', 'tutor-pro' ),
			'message'      => wp_json_encode( 'We wanted to inform you that your subscription to <strong>{plan_name}</strong> has expired. <br>This means your access to the course materials is no longer available. We hope you found the course valuable and made significant progress in your learning journey.<br><br>If you\'d like to renew your subscription, click the button below. <br>We hope to see you back on <strong>{site_name}</strong> soon!' ),
			'footer_text'  => __( 'This is an automated message. Please do not reply', 'tutor-pro' ),
			'placeholders' => EmailPlaceholder::only( array( 'site_url', 'site_name', 'plan_name' ) ),
		);

		$list[ EmailNotification::TO_STUDENTS ]['subscription_cancelled'] = array(
			'label'        => __( 'Subscription Cancelled', 'tutor-pro' ),
			'default'      => 'on',
			'template'     => 'to_student_subscription_cancelled',
			'tooltip'      => 'Email sent to student when subscription is cancelled',
			'subject'      => __( 'Your Subscription Has Been Cancelled', 'tutor-pro' ),
			'heading'      => __( 'Subscription cancelled', 'tutor-pro' ),
			'message'      => wp_json_encode( 'We wanted to inform you that your subscription to <strong>{plan_name}</strong> has been cancelled.<br><br>Thank you for being a part of <strong>{site_name}</strong>. Wishing you success in all your future learning!' ),
			'footer_text'  => __( 'This is an automated message. Please do not reply', 'tutor-pro' ),
			'placeholders' => EmailPlaceholder::only( array( 'site_url', 'site_name', 'plan_name' ) ),
		);

		return $list;
	}

	/**
	 * Get email option data.
	 *
	 * @param string $to_key to key.
	 * @param string $trigger_key key name.
	 *
	 * @return array
	 */
	public function get_email_option_data( $to_key, $trigger_key ) {
		$email_options     = get_option( 'email_template_data' );
		$default_mail_data = ( new EmailData() )->get_recipients();

		return isset( $email_options[ $to_key ][ $trigger_key ] )
				? $email_options[ $to_key ][ $trigger_key ]
				: $default_mail_data[ $to_key ][ $trigger_key ];
	}

	/**
	 * Sent email to student when subscription get activated.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription.
	 *
	 * @return void
	 */
	public function to_student_subscription_activated( $subscription ) {
		$is_enabled = tutor_utils()->get_option( 'email_to_students.subscription_activated' );
		if ( ! $is_enabled || ! $subscription ) {
			return;
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );

		if ( ! $plan ) {
			return;
		}

		$user        = get_userdata( $subscription->user_id );
		$site_url    = get_bloginfo( 'url' );
		$site_name   = get_bloginfo( 'name' );
		$option_data = $this->get_email_option_data( EmailNotification::TO_STUDENTS, 'subscription_activated' );
		$header      = 'Content-Type: ' . $this->email_notification->get_content_type() . "\r\n";

		$replaceable['{plan_name}']        = $plan->plan_name;
		$replaceable['{subscription_url}'] = $this->subscription_model->get_subscription_details_url( $subscription->id );

		$replaceable['{testing_email_notice}'] = '';
		$replaceable['{site_url}']             = $site_url;
		$replaceable['{site_name}']            = $site_name;
		$replaceable['{logo}']                 = isset( $option_data['logo'] ) ? $option_data['logo'] : '';
		$replaceable['{email_heading}']        = $this->email_notification->get_replaced_text( $option_data['heading'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{footer_text}']          = $this->email_notification->get_replaced_text( $option_data['footer_text'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{user_name}']            = tutor_utils()->get_user_name( $user );
		$replaceable['{email_message}']        = $this->email_notification->get_replaced_text( $this->email_notification->prepare_message( $option_data['message'] ), array_keys( $replaceable ), array_values( $replaceable ) );
		$subject                               = $this->email_notification->get_replaced_text( $option_data['subject'], array_keys( $replaceable ), array_values( $replaceable ) );

		ob_start();
		$this->email_notification->tutor_load_email_template( 'to_student_subscription_activated' );
		$email_tpl = ob_get_clean();

		$message = html_entity_decode( $this->email_notification->get_message( $email_tpl, array_keys( $replaceable ), array_values( $replaceable ) ) );
		$this->email_notification->send( $user->user_email, $subject, $message, $header );
	}

	/**
	 * Sent email to student when subscription get renewed.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription.
	 *
	 * @return void
	 */
	public function to_student_subscription_renewed( $subscription ) {
		$is_enabled = tutor_utils()->get_option( 'email_to_students.subscription_renewed' );
		if ( ! $is_enabled || ! $subscription ) {
			return;
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );

		if ( ! $plan ) {
			return;
		}

		$user        = get_userdata( $subscription->user_id );
		$site_url    = get_bloginfo( 'url' );
		$site_name   = get_bloginfo( 'name' );
		$option_data = $this->get_email_option_data( EmailNotification::TO_STUDENTS, 'subscription_renewed' );
		$header      = 'Content-Type: ' . $this->email_notification->get_content_type() . "\r\n";

		$replaceable['{expiry_date}']      = DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->end_date_gmt, null, $user );
		$replaceable['{plan_name}']        = $plan->plan_name;
		$replaceable['{subscription_url}'] = $this->subscription_model->get_subscription_details_url( $subscription->id );

		$replaceable['{testing_email_notice}'] = '';
		$replaceable['{site_url}']             = $site_url;
		$replaceable['{site_name}']            = $site_name;
		$replaceable['{logo}']                 = isset( $option_data['logo'] ) ? $option_data['logo'] : '';
		$replaceable['{email_heading}']        = $this->email_notification->get_replaced_text( $option_data['heading'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{footer_text}']          = $this->email_notification->get_replaced_text( $option_data['footer_text'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{user_name}']            = tutor_utils()->get_user_name( $user );
		$replaceable['{email_message}']        = $this->email_notification->get_replaced_text( $this->email_notification->prepare_message( $option_data['message'] ), array_keys( $replaceable ), array_values( $replaceable ) );
		$subject                               = $this->email_notification->get_replaced_text( $option_data['subject'], array_keys( $replaceable ), array_values( $replaceable ) );

		ob_start();
		$this->email_notification->tutor_load_email_template( 'to_student_subscription_renewed' );
		$email_tpl = ob_get_clean();

		$message = html_entity_decode( $this->email_notification->get_message( $email_tpl, array_keys( $replaceable ), array_values( $replaceable ) ) );
		$this->email_notification->send( $user->user_email, $subject, $message, $header );
	}

	/**
	 * Sent email to student when subscription on hold.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription.
	 *
	 * @return void
	 */
	public function to_student_subscription_hold( $subscription ) {
		$is_enabled = tutor_utils()->get_option( 'email_to_students.subscription_hold' );
		if ( ! $is_enabled || ! $subscription ) {
			return;
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );

		if ( ! $plan ) {
			return;
		}

		$user        = get_userdata( $subscription->user_id );
		$site_url    = get_bloginfo( 'url' );
		$site_name   = get_bloginfo( 'name' );
		$option_data = $this->get_email_option_data( EmailNotification::TO_STUDENTS, 'subscription_hold' );
		$header      = 'Content-Type: ' . $this->email_notification->get_content_type() . "\r\n";

		$replaceable['{plan_name}']        = $plan->plan_name;
		$replaceable['{subscription_url}'] = $this->subscription_model->get_subscription_details_url( $subscription->id );

		$replaceable['{testing_email_notice}'] = '';
		$replaceable['{site_url}']             = $site_url;
		$replaceable['{site_name}']            = $site_name;
		$replaceable['{logo}']                 = isset( $option_data['logo'] ) ? $option_data['logo'] : '';
		$replaceable['{email_heading}']        = $this->email_notification->get_replaced_text( $option_data['heading'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{footer_text}']          = $this->email_notification->get_replaced_text( $option_data['footer_text'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{user_name}']            = tutor_utils()->get_user_name( $user );
		$replaceable['{email_message}']        = $this->email_notification->get_replaced_text( $this->email_notification->prepare_message( $option_data['message'] ), array_keys( $replaceable ), array_values( $replaceable ) );
		$subject                               = $this->email_notification->get_replaced_text( $option_data['subject'], array_keys( $replaceable ), array_values( $replaceable ) );

		ob_start();
		$this->email_notification->tutor_load_email_template( 'to_student_subscription_hold' );
		$email_tpl = ob_get_clean();

		$message = html_entity_decode( $this->email_notification->get_message( $email_tpl, array_keys( $replaceable ), array_values( $replaceable ) ) );
		$this->email_notification->send( $user->user_email, $subject, $message, $header );
	}

	/**
	 * Sent email to student when subscription get cancelled.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription.
	 *
	 * @return void
	 */
	public function to_student_subscription_cancelled( $subscription ) {
		$is_enabled = tutor_utils()->get_option( 'email_to_students.subscription_cancelled' );
		if ( ! $is_enabled || ! $subscription ) {
			return;
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );

		if ( ! $plan ) {
			return;
		}

		$user        = get_userdata( $subscription->user_id );
		$site_url    = get_bloginfo( 'url' );
		$site_name   = get_bloginfo( 'name' );
		$option_data = $this->get_email_option_data( EmailNotification::TO_STUDENTS, 'subscription_cancelled' );
		$header      = 'Content-Type: ' . $this->email_notification->get_content_type() . "\r\n";

		$replaceable['{plan_name}']        = $plan->plan_name;
		$replaceable['{subscription_url}'] = $this->subscription_model->get_subscription_details_url( $subscription->id );

		$replaceable['{testing_email_notice}'] = '';
		$replaceable['{site_url}']             = $site_url;
		$replaceable['{site_name}']            = $site_name;
		$replaceable['{logo}']                 = isset( $option_data['logo'] ) ? $option_data['logo'] : '';
		$replaceable['{email_heading}']        = $this->email_notification->get_replaced_text( $option_data['heading'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{footer_text}']          = $this->email_notification->get_replaced_text( $option_data['footer_text'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{user_name}']            = tutor_utils()->get_user_name( $user );
		$replaceable['{email_message}']        = $this->email_notification->get_replaced_text( $this->email_notification->prepare_message( $option_data['message'] ), array_keys( $replaceable ), array_values( $replaceable ) );
		$subject                               = $this->email_notification->get_replaced_text( $option_data['subject'], array_keys( $replaceable ), array_values( $replaceable ) );

		ob_start();
		$this->email_notification->tutor_load_email_template( 'to_student_subscription_cancelled' );
		$email_tpl = ob_get_clean();

		$message = html_entity_decode( $this->email_notification->get_message( $email_tpl, array_keys( $replaceable ), array_values( $replaceable ) ) );
		$this->email_notification->send( $user->user_email, $subject, $message, $header );
	}

	/**
	 * Sent email to student when subscription get expired.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription.
	 *
	 * @return void
	 */
	public function to_student_subscription_expired( $subscription ) {
		$is_enabled = tutor_utils()->get_option( 'email_to_students.subscription_expired' );
		if ( ! $is_enabled || ! $subscription ) {
			return;
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );

		if ( ! $plan ) {
			return;
		}

		$user        = get_userdata( $subscription->user_id );
		$site_url    = get_bloginfo( 'url' );
		$site_name   = get_bloginfo( 'name' );
		$option_data = $this->get_email_option_data( EmailNotification::TO_STUDENTS, 'subscription_expired' );
		$header      = 'Content-Type: ' . $this->email_notification->get_content_type() . "\r\n";

		$replaceable['{plan_name}']        = $plan->plan_name;
		$replaceable['{subscription_url}'] = $this->subscription_model->get_subscription_details_url( $subscription->id );

		$replaceable['{testing_email_notice}'] = '';
		$replaceable['{site_url}']             = $site_url;
		$replaceable['{site_name}']            = $site_name;
		$replaceable['{logo}']                 = isset( $option_data['logo'] ) ? $option_data['logo'] : '';
		$replaceable['{email_heading}']        = $this->email_notification->get_replaced_text( $option_data['heading'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{footer_text}']          = $this->email_notification->get_replaced_text( $option_data['footer_text'], array_keys( $replaceable ), array_values( $replaceable ) );
		$replaceable['{user_name}']            = tutor_utils()->get_user_name( $user );
		$replaceable['{email_message}']        = $this->email_notification->get_replaced_text( $this->email_notification->prepare_message( $option_data['message'] ), array_keys( $replaceable ), array_values( $replaceable ) );
		$subject                               = $this->email_notification->get_replaced_text( $option_data['subject'], array_keys( $replaceable ), array_values( $replaceable ) );

		ob_start();
		$this->email_notification->tutor_load_email_template( 'to_student_subscription_expired' );
		$email_tpl = ob_get_clean();

		$message = html_entity_decode( $this->email_notification->get_message( $email_tpl, array_keys( $replaceable ), array_values( $replaceable ) ) );
		$this->email_notification->send( $user->user_email, $subject, $message, $header );
	}
}
