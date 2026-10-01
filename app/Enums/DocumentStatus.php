<?php

namespace App\Enums;

/**
 * 單步驟確認類單據的狀態，涵蓋進貨單、採購退貨、出貨單、銷售退貨、倉庫調撥、盤點單、領料單。
 */
enum DocumentStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
