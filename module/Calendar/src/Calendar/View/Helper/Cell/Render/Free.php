<?php



namespace Calendar\View\Helper\Cell\Render;



use Square\Entity\Square;

use Zend\View\Helper\AbstractHelper;



class Free extends AbstractHelper

{



    public function __invoke($user, $userBooking, array $reservations, array $cellLinkParams, Square $square)

    {

        $view = $this->getView();



	    $labelFree = $square->getMeta('label.free', $this->view->t('Free'));



        if ($user && $user->can('calendar.see-data, calendar.create-single-bookings, calendar.create-subscription-bookings')) {

            return $view->calendarCellRenderFreeForPrivileged($reservations, $cellLinkParams, $square);

        } else if ($user) {

          
                $reservationsCount = count($reservations);
                if ($reservationsCount == 1) {
                   $reservation = current($reservations);
                   $booking = $reservation->needExtra('booking');
                   $user = $booking->needExtra('user');
                   $phone = $user->getMeta('phone');
                   $cellLabel = $booking->needExtra('user')->need('alias') . " suche Partner "  . $phone;
                   
                   return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), 'cc-free-partially');
                } else {

                  return $view->calendarCellLink($labelFree, $view->url('square', [], $cellLinkParams), 'cc-free');
                }

        } else {

            return $view->calendarCellLink($labelFree, $view->url('square', [], $cellLinkParams), 'cc-free');

        }

    }



}

