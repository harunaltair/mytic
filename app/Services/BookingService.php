<?php

namespace App\Services;

use App\Models\BookingTransaction;
use App\Models\WorkshopParticipant;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\WorkshopRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    protected $bookingRepository;
    protected $workshopRepository;

    public function __construct(BookingRepositoryInterface $bookingRepository, WorkshopRepositoryInterface $workshopRepository)
    {
        $this->bookingRepository = $bookingRepository;
        $this->workshopRepository = $workshopRepository;
    }

    public function storeBooking(array $data)
    {
        // get existing session data
        $existingData = $this->bookingRepository->getOrderDataFromSession();

        // Merge existing data with new data
        $updatedData = array_merge($existingData, $data);

        // Save the updated data to Session
        $this->bookingRepository->saveToSession($updatedData);

        return $updatedData;
    }

    public function isBookingSessionAvailable()
    {
        // memeriksa apakah ada data di session sebelumnya, jika tidak ada maka user harus mengulang dari awal
        return $this->bookingRepository->getOrderDataFromSession() !== null;
    }

    public function getBookingDetails()
    {
        $orderData = $this->bookingRepository->getOrderDataFromSession();

        if (empty($orderData)) {
            return null; // atau lempar exception sesuai kebutuhan
        }

        $workshop = $this->workshopRepository->find($orderData['workshop_id']);

        $quantity = isset($orderData['quantity']) ? (int)$orderData['quantity'] : 1;

        $subtotalAmount = $workshop->price * $quantity;

        $taxRate = 0.11; // 11% ppn
        $totalTax = $subtotalAmount * $taxRate;

        $totalAmount = $subtotalAmount + $totalTax;

        $orderData['sub_total_amount'] = $subtotalAmount;
        $orderData['total_tax'] = $totalTax;
        $orderData['total_amount'] = $totalAmount;

        $this->bookingRepository->saveToSession($orderData);

        return compact('orderData', 'workshop');
    }

    public function finalizeBookingAndPayment(array $paymentData)
    {
        $orderData = $this->bookingRepository->getOrderDataFromSession();

        if (!$orderData) {
            throw new \Exception('Booking data is missing from session.');
        }

        Log::info('Order Data:', $orderData); // Add this line to log the order data

        if (!isset($orderData['total_amount'])) {
            throw new \Exception('Total amount is missing from the order data.');
        }

        if (isset($paymentData['proof'])) {
            $proofPath = $paymentData['proof']->store('proofs', 'public');
        }

        // mulai transaksi database, jika ada error maka rollback/tidak terjadi perubahan
        DB::beginTransaction();

        try {
            $bookingTransaction = BookingTransaction::create([
                'name'                => $orderData['name'],
                'email'               => $orderData['email'],
                'phone'               => $orderData['phone'],
                'customer_bank_name'  => $paymentData['customer_bank_name'],
                'customer_bank_number'=> $paymentData['customer_bank_number'],
                'customer_bank_account'=> $paymentData['customer_bank_account'],
                'proof'               => $proofPath ?? null,
                'quantity'            => $orderData['quantity'],
                'total_amount'        => $orderData['total_amount'],
                'is_paid'             => false,
                'workshop_id'         => $orderData['workshop_id'],
                'booking_trx_id'      => BookingTransaction::generateUniqueTrxId(),
            ]);

            foreach ($orderData['participants'] as $participant) {
                WorkshopParticipant::create([
                    'name' => $participant['name'],
                    'occupation' => $participant['occupation'],
                    'email' => $participant['email'],
                    'workshop_id' => $orderData['workshop_id'],
                    'booking_transaction_id' => $bookingTransaction->id,
                ]);
            }

            // commit transaksi jika berhasil
            DB::commit();

            // clear session setelah booking berhasil
            $this->bookingRepository->clearSession();

            // kembalikan id booking transaction, nanti akan digunakan untuk halaman selesai booking
            return $bookingTransaction->id;

        } catch (\Exception $e) {
            Log::error("Payment finalization failed: " . $e->getMessage());

            // rollback jika ada error
            DB::rollback();

            throw $e;
        }
    }

    public function getMyBookingDetails(array $data)
    {
        return $this->bookingRepository->findByTrxIdAndPhoneNumber($data['booking_trx_id'], $data['phone']);
    }

}
