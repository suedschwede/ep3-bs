<?php



namespace Booking\Service\Listener;



use Backend\Service\MailService as BackendMailService;

use Base\Manager\OptionManager;

use Base\View\Helper\DateRange;

use Booking\Manager\ReservationManager;

use Booking\Manager\BookingManager;

use Square\Manager\SquareManager;

use User\Manager\UserManager;

use User\Service\MailService as UserMailService;

use Zend\EventManager\AbstractListenerAggregate;

use Zend\EventManager\Event;

use Zend\EventManager\EventManagerInterface;

use Zend\I18n\Translator\TranslatorInterface;

use Zend\I18n\View\Helper\DateFormat;



class NotificationListener extends AbstractListenerAggregate

{



    protected $optionManager;

    protected $reservationManager;

    protected $squareManager;

    protected $userManager;

    protected $userMailService;

	protected $backendMailService;

    protected $dateFormatHelper;

    protected $dateRangeHelper;

    protected $translator;



    public function __construct(OptionManager $optionManager, ReservationManager $reservationManager, SquareManager $squareManager,

	    UserManager $userManager, UserMailService $userMailService, BackendMailService $backendMailService,

	    DateFormat $dateFormatHelper, DateRange $dateRangeHelper, TranslatorInterface $translator,BookingManager $bookingManager)

    {

        $this->optionManager = $optionManager;

        $this->reservationManager = $reservationManager;

        $this->squareManager = $squareManager;

        $this->userManager = $userManager;

        $this->userMailService = $userMailService;

	    $this->backendMailService = $backendMailService;

       $this->bookingManager = $bookingManager;

        $this->dateFormatHelper = $dateFormatHelper;

        $this->dateRangeHelper = $dateRangeHelper;

        $this->translator = $translator;

    }



    public function attach(EventManagerInterface $events)

    {

        $events->attach('create.single', array($this, 'onCreateSingle'));

        $events->attach('cancel.single', array($this, 'onCancelSingle'));

    }



    public function onCreateSingle(Event $event)

    {

        $booking = $event->getTarget();

        $reservation = current($booking->getExtra('reservations'));        

        $square = $this->squareManager->get($booking->need('sid'));

        $user = $this->userManager->get($booking->need('uid'));

       
        $dateFormatHelper = $this->dateFormatHelper;

        $dateRangerHelper = $this->dateRangeHelper;



	$reservationTimeStart = explode(':', $reservation->need('time_start'));

        $reservationTimeEnd = explode(':', $reservation->need('time_end'));



        $reservationStart = new \DateTime($reservation->need('date'));

        $reservationStart->setTime($reservationTimeStart[0], $reservationTimeStart[1]);



        $reservationEnd = new \DateTime($reservation->need('date'));

        $reservationEnd->setTime($reservationTimeEnd[0], $reservationTimeEnd[1]);

        $possibleReservations = $this->reservationManager->getInRange($reservationStart, $reservationEnd);
        $possibleBookings = $this->bookingManager->getByReservations($possibleReservations);

        $reservationsCount = count($possibleBookings);

        foreach ($possibleBookings as $bid => $booking2) {
          if ($booking2->need('uid') != $booking->need('uid')) {
            if ($booking2->need('status') != 'cancelled') {
              $square1 = $this->squareManager->get($booking2->need('sid'));
              if ($square1) {
                if ($square1->need('name') == $square->need('name'))  {
                  $booking1 = $booking2;
                }
              }
            }  
          }

        }

        $subject = sprintf($this->t('Your %s-booking for %s'),

            $this->optionManager->get('subject.square.type'),

            $dateFormatHelper($reservationStart, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT));



        $message = sprintf($this->t('we have reserved %s %s, %s for you. Thank you for your booking.'),

            $this->optionManager->get('subject.square.type'),

            $square->need('name'),

            $dateRangerHelper($reservationStart, $reservationEnd));



        if ($booking->getMeta('player-names')) {

            $message .= "\n\nAngegebene Mitspieler:";
            
            if ($booking1) {
              $uid = $booking1->need('uid');
              $user2 = $this->userManager->get($uid);
              $message .= " 1. " . $user2->need('alias');
            }


            foreach (unserialize($booking->getMeta('player-names')) as $i => $playerName) {

                $message .= sprintf("\n%s. %s",

                    $i + 1, $playerName['value']);

            }

        }



        if ($user->getMeta('notification.bookings', 'true') == 'true') {

            $this->userMailService->send($user, $subject, $message);

        }

        if ($booking1) {
          $uid = $booking1->need('uid');
          $user2 = $this->userManager->get($uid);
          $subject1 = sprintf($this->t('Partner gefunden %s-Buchung am %s'),
            $this->optionManager->get('subject.square.type'),
            $dateFormatHelper($reservationStart, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT));
         
          
         $message1 = sprintf($this->t('Fuer deine Buchung hat sich ein Partner gefunden'),
            $this->optionManager->get('subject.square.type'),
            $square->need('name'),
            $dateRangerHelper($reservationStart, $reservationEnd));
        
          $message1 .= " - " . $user->need('alias');

          $this->userMailService->send($user2, $subject1, $message1);
        }

        
        

	    if ($this->optionManager->get('client.contact.email.user-notifications')) {



		    $backendSubject = sprintf($this->t('%s\'s %s-booking for %s'),

		        $user->need('alias'), $this->optionManager->get('subject.square.type'),

			    $dateFormatHelper($reservationStart, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT));



		    $addendum = sprintf($this->t('Originally sent to %s (%s).'),

	            $user->need('alias'), $user->need('email'));
                    
                    if ($booking1) {
                      $uid = $booking1->need('uid');
                      $user2 = $this->userManager->get($uid);
                      $addendum .= " " . $user2->need('alias') . " " . $square1->need('name');
                    }

	        $this->backendMailService->send($backendSubject, $message, array(), $addendum);

        }

    }



    public function onCancelSingle(Event $event)

    {

        $booking = $event->getTarget();

        $reservations = $this->reservationManager->getBy(['bid' => $booking->need('bid')], 'date ASC', 1);

        $reservation = current($reservations);

        $square = $this->squareManager->get($booking->need('sid'));

        $user = $this->userManager->get($booking->need('uid'));



        $dateRangerHelper = $this->dateRangeHelper;



	    $reservationTimeStart = explode(':', $reservation->need('time_start'));

        $reservationTimeEnd = explode(':', $reservation->need('time_end'));



        $reservationStart = new \DateTime($reservation->need('date'));

        $reservationStart->setTime($reservationTimeStart[0], $reservationTimeStart[1]);



        $reservationEnd = new \DateTime($reservation->need('date'));

        $reservationEnd->setTime($reservationTimeEnd[0], $reservationTimeEnd[1]);

        $possibleReservations = $this->reservationManager->getInRange($reservationStart, $reservationEnd);
        $possibleBookings = $this->bookingManager->getByReservations($possibleReservations);

        $reservationsCount = count($possibleBookings);

        foreach ($possibleBookings as $bid => $booking2) {
          if ($booking2->need('uid') != $booking->need('uid')) {
            if ($booking2->need('status') != 'cancelled') {
              $square1 = $this->squareManager->get($booking2->need('sid'));
              if ($square1) {
                if ($square1->need('name') == $square->need('name'))  {
                  $booking1 = $booking2;
                }
              }
            }
           
          }

        }





        $subject = sprintf($this->t('Your %s-booking has been cancelled'),

            $this->optionManager->get('subject.square.type'));



        $message = sprintf($this->t('we have just cancelled %s %s, %s for you.'),

            $this->optionManager->get('subject.square.type'),

            $square->need('name'),

            $dateRangerHelper($reservationStart, $reservationEnd));



        if ($user->getMeta('notification.bookings', 'true') == 'true') {

            $this->userMailService->send($user, $subject, $message);

        }

         if ($booking1) {
          $uid = $booking1->need('uid');
          $user2 = $this->userManager->get($uid);
          
          $subject1 = sprintf($this->t('Dein Partner hat storniert %s-Buchung'),
            $this->optionManager->get('subject.square.type'));

         
           
          $message1 = sprintf($this->t('Dein Partner hat seine Buchung storniert'),
          $this->optionManager->get('subject.square.type'),
          $square->need('name'),
          $dateRangerHelper($reservationStart, $reservationEnd));
        
          $message1 .= " - " . $user->need('alias');

          $this->userMailService->send($user2, $subject1, $message1);
        }





	    if ($this->optionManager->get('client.contact.email.user-notifications')) {



		    $backendSubject = sprintf($this->t('%s\'s %s-booking has been cancelled'),

		        $user->need('alias'), $this->optionManager->get('subject.square.type'));



		    $addendum = sprintf($this->t('Originally sent to %s (%s).'),

	            $user->need('alias'), $user->need('email'));



	        $this->backendMailService->send($backendSubject, $message, array(), $addendum);

        }

    }



    protected function t($message)

    {

        return $this->translator->translate($message);

    }



}

