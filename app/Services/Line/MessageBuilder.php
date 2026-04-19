<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class MessageBuilder
{
    public function welcome(): string
    {
        return 'ยินดีต้อนรับครับ กดปุ่มยืนยันห้องพักเพื่อเปิดหน้าเชื่อมบัญชี LINE กับห้องพัก แล้วกรอกรหัส 6 หลักจากเจ้าของหอ';
    }

    public function pendingLink(): string
    {
        return 'บัญชียังไม่ถูกเชื่อมกับห้องพัก กดปุ่มยืนยันห้องพักเพื่อเปิดหน้าเชื่อมบัญชี แล้วกรอกรหัส 6 หลัก';
    }

    public function linkSuccess(string $name, ?string $roomNumber): string
    {
        return sprintf('เชื่อม LINE สำเร็จ: %s ห้อง %s', $name, $roomNumber ?? '-');
    }

    public function linkTokenInvalid(): string
    {
        return 'รหัสเชื่อมต่อไม่ถูกต้องหรือหมดอายุแล้ว';
    }

    public function latestInvoice(?Invoice $invoice): string
    {
        if (! $invoice) {
            return 'ยังไม่มีบิลที่ต้องชำระ';
        }

        $room = $invoice->room_id ? Room::query()->find($invoice->room_id) : null;
        $roomNumber = $room?->room_number ?? '-';
        $dueDate = Carbon::parse((string) $invoice->due_date)->format('d/m/Y');

        return sprintf(
            'บิลล่าสุด %s ห้อง %s ยอด %s บาท ครบกำหนด %s',
            $invoice->invoice_no,
            $roomNumber,
            number_format((float) $invoice->total_amount, 2),
            $dueDate,
        )."\n".$invoice->signedResidentUrl();
    }

    public function paymentLink(?Invoice $invoice): string
    {
        if (! $invoice) {
            return 'ยังไม่มีบิลที่ต้องชำระ';
        }

        return 'ชำระเงินได้ที่: '.$invoice->signedResidentUrl();
    }

    /**
     * @param Collection<int, Payment> $payments
     */
    public function paymentHistory(Collection $payments): string
    {
        if ($payments->isEmpty()) {
            return 'ยังไม่มีประวัติการชำระเงิน';
        }

        return 'ประวัติการจ่ายล่าสุด'."\n".$payments->map(function (Payment $payment): string {
            $paymentDate = Carbon::parse((string) $payment->payment_date)->format('d/m/Y');

            return sprintf(
                '- %s %s บาท (%s)',
                $paymentDate,
                number_format((float) $payment->amount, 2),
                $payment->status
            );
        })->implode("\n");
    }

    public function repairHelp(): string
    {
        return "รับเรื่องแจ้งซ่อมแล้ว\nกรุณาส่งรายละเอียดปัญหา + เลขห้อง เช่น: ซ่อม แอร์ไม่เย็น ห้อง A203";
    }

    public function repairLink(string $url): string
    {
        return "แจ้งซ่อมผ่านฟอร์มนี้ได้ทันที\n{$url}";
    }

    /**
     * @param Collection<int, string> $announcements
     */
    public function announcements(Collection $announcements): string
    {
        if ($announcements->isEmpty()) {
            return 'ยังไม่มีประกาศล่าสุดในขณะนี้';
        }

        return 'ประกาศล่าสุด'."\n".$announcements->implode("\n");
    }

    public function contactOwner(?string $name, ?string $phone, ?string $lineId): string
    {
        $lines = ['ติดต่อเจ้าของหอ'];

        if ($name) {
            $lines[] = 'ชื่อ: '.$name;
        }

        if ($phone) {
            $lines[] = 'โทร: '.$phone;
        }

        if ($lineId) {
            $lines[] = 'LINE: '.$lineId;
        }

        if (count($lines) === 1) {
            $lines[] = 'ยังไม่ได้ตั้งค่าช่องทางติดต่อในระบบ';
        }

        return implode("\n", $lines);
    }

    public function unsupported(): string
    {
        return "คำสั่งที่รองรับ:\n- บิล\n- จ่าย\n- ประวัติ\n- แจ้งซ่อม\n- ประกาศ\n- ติดต่อเจ้าของ\n- LINK ABC123 หรือกดปุ่มยืนยันห้องพักใน LINE";
    }
}
