<style>
    @font-face {
        font-family: 'Roboto';
        font-style: normal;
        font-weight: 400;
        src: url("{{ storage_path('fonts/Roboto-Regular.ttf') }}") format("truetype");
    }

    @font-face {
        font-family: 'Poppins';
        font-style: normal;
        font-weight: 400;
        src: url("{{ storage_path('fonts/Poppins-Regular.ttf') }}") format("truetype");
    }

    @page {
        margin: 12mm 10mm;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #374151;
        margin: 0;
        padding: 0;
    }

    table {
        border-collapse: collapse;
    }

    .header-container {
        width: 100%;
        border: 1px solid #45474b;
        table-layout: fixed;
        border-collapse: collapse;
        margin-bottom: 18px;
        border-radius: 8px;
    }

    .header-container td {
        border: 1px solid #45474b;
        padding: 9px 8px;
        vertical-align: middle;
    }

    .logo-cell {
        width: 125px;
        background: #FFFFFF;
        text-align: center;
    }

    .logo-cell img {
        width: 125px;
    }

    .title-primary {
        background: #f7f7f7;
        text-align: center;
        font-family: 'Roboto', sans-serif;
        font-size: 12px;
        font-weight: bold;
        color: #3F3F46;
        letter-spacing: 1px;
    }

    .meta-cell {
        width: 170px;
        background: #f7f7f7;
        text-align: center;
        font-size: 9px;
        font-weight: bold;
        color: #374151;
        line-height: 1.2;
        font-family: 'Roboto', sans-serif;
    }

    .qr-cell {
        width: 90px;
        background: #FFFFFF;
        text-align: center;
    }

    .qr-placeholder {
        border: 1px solid #9CA3AF;
        height: 60px;
        line-height: 60px;
        font-size: 9px;
        color: #6B7280;
    }

    .section {
        margin-top: 18px;
    }

    .section-title {
        font-family: 'Poppins', sans-serif;
        background: #f7f7f7;
        color: #111827;
        font-size: 11px;
        font-weight: bold;
        padding: 8px 10px;
        border: 1px solid rgb(0, 0, 0);
        border-bottom: none;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .info-table td {
        border: 0.25px solid rgb(0, 0, 0);
        padding: 8px;
    }

    .label {
        width: 25%;
        font-family: 'Poppins', sans-serif;
        background: #FAFAFA;
        font-weight: bold;
        color: #4B5563;
        text-transform: uppercase;
    }

    .label-primary {
        width: 24%;
        font-family: 'Poppins', sans-serif;
        background: #FAFAFA;
        font-weight: bold;
        color: #4B5563;
        text-transform: uppercase;
        text-align: center;
    }

    .label-primary-aux {
        width: 24%;
        font-family: 'Poppins', sans-serif;
        background: #FAFAFA;
        font-weight: bold;
        color: #4B5563;
        text-transform: uppercase;
        text-align: right;
    }

    .value {
        width: 37%;
        color: #111827;
        font-family: 'Roboto', sans-serif;
    }

    .value-primary {
        width: 37%;
        color: #111827;
        font-family: 'Roboto', sans-serif;
        text-align: center;
        font-size: 10px;
    }

    .product-image {
        width: 180px;
        height: 220px;
        display: block;
        margin: 0 auto;
        object-fit: contain;
    }

    .image-cell {
        text-align: center;
        vertical-align: middle;
        padding: 10px;
    }

    .metric-box {
        text-align: center;
        padding: 12px 8px !important;
    }

    .metric-value {
        font-family: 'Roboto', sans-serif;
        font-size: 18px;
        font-weight: bold;
        color: #111827;
        margin: 4px 0;
    }

    .metric-unit {
        font-size: 8px;
        color: #9CA3AF;
        text-transform: uppercase;
    }

    .total-column {
        background: #F3F4F6;
        text-align: center;
        font-weight: bold;
        color: #111827;
    }

    .curve-total {
        background: #E8EEF8;
        color: #1E3A8A;
        font-family: 'Roboto', sans-serif;
        font-weight: bold;
        text-align: center;
    }

    .grand-total {
        background: #D1D5DB;
        color: #111827;
        font-family: 'Roboto', sans-serif;
        font-size: 11px;
        font-weight: bold;
        text-align: center;
        border-left: 1.5px solid #6B7280 !important;
    }

    .two-tables-container {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        border: none !important;
    }

    .two-tables-container>tbody>tr>td {
        border: none !important;
        padding: 0;
        vertical-align: top;
    }

    /* Opcion B: 70% / 30% (Por si necesitas más diferencia) */
    .table-column-70 {
        width: 70%;
    }

    .table-column-30 {
        width: 30%;
    }

    .total-row-label {
        background: #374151;
        color: #FFF;
        font-family: 'Poppins', sans-serif;
        font-weight: bold;
        text-align: center;
    }

    .total-row-value {
        background: #F3F4F6;
        font-family: 'Roboto', sans-serif;
        font-weight: bold;
        font-size: 11px;
        text-align: center;
    }

    .diff-row-label {
        background: #111827;
        color: #FFF;
        font-family: 'Poppins', sans-serif;
        font-weight: bold;
        text-align: center;
    }

    .diff-negative {
        background: #FEE2E2;
        color: #B91C1C;
        font-weight: bold;
        text-align: center;
    }

    .diff-zero {
        background: #DCFCE7;
        color: #15803D;
        font-weight: bold;
        text-align: center;
    }

    .diff-positive {
        background: #DBEAFE;
        color: #1D4ED8;
        font-weight: bold;
        text-align: center;
    }

    .size-chip {
        display: inline-block;
        padding: 3px 7px;
        margin: 2px;
        border: 1px solid #D1D5DB;
        background: #F9FAFB;
        border-radius: 3px;
        font-size: 9px;
        font-weight: bold;
        color: #374151;
    }
</style>
