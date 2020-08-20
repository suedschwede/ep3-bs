<?php



namespace Calendar\View\Helper\Cell\Render;



use Booking\Service\BookingStatusService;

use Zend\View\Helper\AbstractHelper;



class OccupiedForPrivileged extends AbstractHelper

{



    protected $bookingStatusService;



    public function __construct(BookingStatusService $bookingStatusService)

    {

        $this->bookingStatusService = $bookingStatusService;

    }



    public function __invoke(array $reservations, array $cellLinkParams)

    {

        $view = $this->getView();



        $reservationsCount = count($reservations);

        $nameData = "";
        $cellLabel ="";

        if ($reservationsCount > 1) {

            foreach ($reservations as $reservation) {
               $booking = $reservation->needExtra('booking'); 
               if ($cellLabel) {
                 $cellLabel =  $cellLabel . ' / ' .  $booking->needExtra('user')->need('alias');
               } else {
                 $cellLabel = $booking->needExtra('user')->need('alias');
                 
               }
            }


            return $view->calendarCellLink($this->view->t($cellLabel), $view->url('backend/booking/edit', [], $cellLinkParams), 'cc-single');

        } else {

            $reservation = current($reservations);

            $booking = $reservation->needExtra('booking');
            $quantity = $booking->need('quantity');

            $bookingStatusColor = $this->bookingStatusService->getStatusColor($booking->getBillingStatus());

            $playerNames = $booking->getMeta('player-names');
            if ($playerNames) {
              $playerNames = @unserialize($playerNames);
              if ($playerNames) {
                foreach ($playerNames as $playerData) {
                  $nameData = $playerData['value'];
                }

              }
            }

            $notes = $booking->getMeta('notes');


            if ($bookingStatusColor) {

                $cellStyle = 'outline: solid 3px ' . $bookingStatusColor;

            } else {

                $cellStyle = null;

            }

              if ($notes) {
              $cellStyle = 'outline: solid 3px #ff6600';
            }


            $prefix = "";
            if ($quantity == 4) {
              $prefix = "Doppel ";
            }


            if ($nameData) {
              $cellLabel = $prefix . $booking->needExtra('user')->need('alias') . ' / ' . $nameData;
            } else {
              $cellLabel = $prefix . $booking->needExtra('user')->need('alias');
            }


            //$cellLabel = $booking->needExtra('user')->need('alias');

            $cellGroup = ' cc-group-' . $booking->need('bid');



            switch ($booking->need('status')) {

                case 'single':

                    return $view->calendarCellLink($cellLabel, $view->url('backend/booking/edit', [], $cellLinkParams), 'cc-multiple' . $cellGroup, null, $cellStyle);

                case 'subscription':

                    return $view->calendarCellLink($cellLabel, $view->url('backend/booking/edit', [], $cellLinkParams), 'cc-multiple' . $cellGroup, null, $cellStyle);

            }

        }

    }



}

