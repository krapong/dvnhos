<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case TrayReady = 'tray_ready';
    case Delivering = 'delivering';
    case Delivered = 'delivered';
    case WardReceived = 'ward_received';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case NeedClarification = 'need_clarification';
    case Stat = 'stat';
}
