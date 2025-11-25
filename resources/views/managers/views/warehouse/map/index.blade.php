@extends('layouts.map')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Mapa del Almacén</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1e40af;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --dark-lighter: #1e293b;
            --border: #374151;
            --text: #e5e7eb;
            --text-muted: #9ca3af;
            --bg-dark: #0b1022;
        }

        * {
            box-sizing: border-box;
        }

        body, .warehouse-container {
            margin: 0;
            font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif;

            color: #000;
        }

        .warehouse-container {
            display: flex;
            flex-direction: column;
            height: 100vh;

        }

        .warehouse-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0.75rem 1rem;
            background: var(--dark);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .warehouse-header-title {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .warehouse-header-controls {
            display: none;
        }

        .floor-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .floor-selector label {
            font-size: 0.8rem;
            color: #000;
            white-space: nowrap;
            font-weight: 500;
            margin-right: 0.25rem;
            display: flex;
            align-items: center;
        }

        .floor-selector label i {
            font-size: 0.85rem;
        }

        .floor-btn {
            background: rgba(255, 255, 255, 0.05);
            color: #000;
            border: 1px solid var(--border);
            padding: 0.35rem 0.65rem;
            border-radius: 0.4rem;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .floor-btn:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: #000000;
            color: #000000;
        }

        .floor-btn.active {
            background: #000000;
            border-color: #000000;
            color: white;
            font-weight: 600;
        }

        .toolbar-group {
            display: flex;
            gap: 0.4rem;
            flex-wrap: nowrap;
        }

        .toolbar-btn {
            background: #000;
            color: #ffffff;
            border: 1px solid #ffffff;
            padding: 10px 15px;
            border-radius: 0.4rem;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s
            ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            font-weight: 500;
        }

        .toolbar-btn:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #000000;
            color: #000000;
        }

        .toolbar-btn i {
            font-size: 0.85rem;
        }

        .search-box {
            flex: 0 0 auto;
            min-width: 160px;
            max-width: 280px;
        }

        .search-box input {
            width: 100%;
            padding: 0.4rem 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 0.4rem;
            color: #000;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #000000;
            background: #081A280d;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box input::placeholder {
            color: #000;
        }

        /* Main content area */
        .warehouse-content {
            display: flex;
            flex: 1;
            gap: 1rem;
            padding: 1rem;
            overflow: hidden;
            margin-top: 90px;
        }

        .content-main {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
        }

        .content-toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.75rem;
            border-radius: 0.75rem;
            background: #ffffff;
            border: 1px solid #081A280d;
        }

        .map-container {
            flex: 1;
            min-width: 300px;
            position: relative;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            overflow: hidden;

        }

        .stage {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        svg {
            width: 100%;
            height: 100%;
            touch-action: none;
        }

        .grid {
            stroke: #1f2937;
            stroke-width: 1;
        }

        .warehouse {
            fill: none;
            stroke: transparent;
            stroke-width: 3;
            rx: 12;
        }

        .shelf {
            fill: #93c5fd22;
            stroke: #60a5fa;
            stroke-width: 1.4;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .shelf:hover {
            fill: #93c5fd44;
            stroke-width: 2;
        }

        .shelf-text {
            font-size: 9px;
            fill: #e5e7eb;
            pointer-events: none;
            text-anchor: middle;
            dominant-baseline: middle;
            opacity: 0.9;
        }

        /* Color variants */
        .shelf--gris {
            fill: #9ca3af33;
            stroke: #9ca3af;
        }

        .shelf--verde {
            fill: #22c55e33;
            stroke: #16a34a;
        }

        .shelf--ambar {
            fill: #f59e0b33;
            stroke: #d97706;
        }

        .shelf--rojo {
            fill: #ef444433;
            stroke: #dc2626;
        }

        .shelf--azul {
            fill: #60a5fa33;
            stroke: #3b82f6;
        }

        .shelf--morado {
            fill: #a78bfa33;
            stroke: #8b5cf6;
        }

        .footer-hint {
            position: absolute;
            left: 0.75rem;
            bottom: 0.65rem;
            color: #000;
            font-size: 0.85rem;
            text-shadow: 0 1px 2px var(--bg-dark);
            z-index: 5;
            background: rgba(15, 23, 42, 0.7);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            backdrop-filter: blur(2px);
        }

        /* Info panel */
        .info-panel {
            flex: 0 0 300px;
            width: 300px;
            background: #ffffff;
            border: 1px solid #081A280d;
            border-radius: 0.75rem;
            padding: 1rem;
            overflow-y: auto;
            max-height: calc(100vh - 150px);
        }

        .info-panel-section {
            margin-bottom: 1.5rem;
        }

        .info-panel-section:last-child {
            margin-bottom: 0;
        }

        .info-panel-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.375rem;
            background: #081A280d;
            font-size: 0.85rem;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 0.25rem;
            border: 1px solid var(--border);
        }

        .legend-color.occupied {
            background: #ef4444;
        }

        .legend-color.partial {
            background: #f59e0b;
        }

        .legend-color.available {
            background: #10b981;
        }

        .legend-color.empty {
            background: #9ca3af;
        }

        /* Floor selector in panel */
        .floor-selector-panel {
            background: #081A280d;
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #081A280d;
            margin-bottom: 1.5rem !important;
        }

        .floor-selector-panel-content {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .floor-btn-panel {
            flex: 0 0 calc(50% - 0.25rem);
            background: rgba(255, 255, 255, 0.05);
            color: #000;
            border: 1px solid var(--border);
            padding: 0.45rem 0.65rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: center;
        }

        .floor-btn-panel:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #000000;
            color: #000000;
        }

        .floor-btn-panel.active {
            background: #000000;
            border-color: #000000;
            color: white;
            font-weight: 600;
        }

        /* Modal mejorado */
        .modal-shelf {
            display: none;
            position: fixed;
            z-index: 1050;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.2s ease;
            overflow-y: auto;
            padding: 1rem;
        }

        .modal-shelf.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            margin: auto;
            overflow: hidden;
        }

        .modal-shelf.show .modal-content {
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            color: #000;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background: var(--border);
            color: var(--danger);
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: #081A280d;
        }

        /* Shelf details */
        .shelf-details {
            background: #081A280d;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .shelf-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .detail-value {
            font-size: 1rem;
            color: #000;
            font-weight: 500;
        }

        /* Faces container */
        .faces-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .face-block {
            background: #081A280d;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .face-header {
            background: #081A280d;
            color: #000000;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .face-content {
            padding: 1rem;
        }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 0.5rem;
        }

        .slot-item {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: 1px solid var(--border);
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .slot-item:hover {
            transform: scale(1.05);
            border-color: #000000;
        }

        .slot-item.empty {
            background: rgba(156, 163, 175, 0.1);
            color: #000;
        }

        .slot-item.occupied {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border-color: var(--success);
        }

        .slot-item.warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border-color: var(--warning);
        }

        .slot-item.critical {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border-color: var(--danger);
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.55rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
            background: #081A280d;
            color: #000;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--border);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #000000;
            border-color: #000000;
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        /* Loading state */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 1.1rem;
            color: #000;
            gap: 0.75rem;
        }

        .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--border);
            border-top-color: #000000;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsiveness */
        @media (max-width: 1024px) {
            .warehouse-content {
                flex-direction: column;
                gap: 1rem;
            }

            .info-panel {
                width: 100%;
                max-height: 250px;
            }

            .faces-container {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .search-box {
                min-width: 140px;
                max-width: 200px;
            }

            .toolbar-btn span {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .warehouse-header-title {
                font-size: 1.1rem;
            }

            .warehouse-content {
                flex-direction: column;
                padding: 0.75rem;
                gap: 0.75rem;
            }

            .content-main {
                gap: 0.75rem;
            }

            .content-toolbar {
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0.5rem;
            }

            .floor-selector {
                flex: 0 1 auto;
            }

            .floor-selector label {
                display: flex;
            }

            .search-box {
                flex: 1;
                min-width: 140px;
                max-width: 200px;
            }

            .toolbar-group {
                gap: 0.3rem;
            }

            .toolbar-btn span {
                display: inline;
            }

            .map-container {
                min-height: 300px;
            }

            .info-panel {
                flex: 0 0 100%;
                max-height: 200px;
                width: 100%;
            }

            .floor-selector-panel-content {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.5rem;
            }

            .floor-btn-panel {
                flex: none;
                width: 100%;
            }


            .faces-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .warehouse-header-title {
                font-size: 0.95rem;
            }

            .warehouse-header-title i {
                display: none;
            }

            .content-toolbar {
                flex-direction: column;
                gap: 0.4rem;
                padding: 0.5rem;
            }

            .floor-selector {
                width: 100%;
                gap: 0.2rem;
            }

            .floor-selector label {
                display: none;
            }

            .floor-btn {
                padding: 0.3rem 0.45rem;
                font-size: 0.65rem;
            }

            .toolbar-group {
                width: 100%;
                justify-content: space-between;
                gap: 0.3rem;
            }

            .toolbar-btn {
                flex: 1;
                padding: 0.3rem 0.4rem;
                font-size: 0.65rem;
                justify-content: center;
            }

            .toolbar-btn i {
                font-size: 0.7rem;
            }

            .toolbar-btn span {
                display: none;
            }

            .search-box {
                width: 100%;
                min-width: auto;
                max-width: none;
            }

            .search-box input {
                padding: 0.3rem 0.5rem;
                font-size: 0.7rem;
            }

            .info-panel {
                padding: 0.75rem;
            }

            .floor-selector-panel-content {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.4rem;
            }

            .floor-btn-panel {
                padding: 0.4rem 0.5rem;
                font-size: 0.75rem;
            }

            .shelf-details-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        /* ======= NUEVOS ESTILOS PARA VECTORES MEJORADOS ======= */

        /* Stand de una cara */
        .stand-single-face {
            width: 120px;
            height: 85px;
            border-radius: 8px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4),
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            border: 1px solid #374151;
            transition: all 0.3s ease;
        }

        .stand-single-face:hover {
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            border-color: #60a5fa;
        }

        /* Stand de dos caras */
        .stand-dual-face {
            width: 140px;
            height: 105px;
            border-radius: 8px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 10px;
            box-shadow:
                0 8px 24px rgba(0, 0, 0, 0.4),
                0 0 1px rgba(139, 92, 246, 0.3) inset,
                0 0 1px rgba(6, 182, 212, 0.3) inset;
            position: relative;
            border: 2px solid #374151;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: perspective(1000px);
        }

        .stand-dual-face:hover {
            box-shadow:
                0 12px 32px rgba(0, 0, 0, 0.5),
                0 0 8px rgba(139, 92, 246, 0.4) inset,
                0 0 8px rgba(6, 182, 212, 0.4) inset;
            transform: translateY(-3px) perspective(1000px) rotateX(5deg);
            border-color: #8b5cf6;
        }

        .stand-dual-face::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(139, 92, 246, 0.1) 0%,
                rgba(6, 182, 212, 0.1) 100%);
            border-radius: 8px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stand-dual-face:hover::before {
            opacity: 1;
        }

        /* Leyenda mejorada de estados */
        .state-indicator {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1);
            transition: transform 0.2s ease;
        }

        .state-indicator:hover {
            transform: scale(1.2);
        }

        .state-empty {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }

        .state-available {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4), inset 0 1px 2px rgba(255, 255, 255, 0.1);
        }

        .state-partial {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4), inset 0 1px 2px rgba(255, 255, 255, 0.1);
        }

        .state-occupied {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4), inset 0 1px 2px rgba(255, 255, 255, 0.1);
        }

        /* Contenedor de vectores SVG en el mapa */
        .svg-shelf-vector {
            cursor: pointer;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            transition: filter 0.2s ease;
        }

        .svg-shelf-vector:hover {
            filter: drop-shadow(0 4px 12px rgba(59, 130, 246, 0.4));
        }

        .svg-shelf-vector g {
            transition: opacity 0.2s ease;
        }

        .svg-shelf-vector:hover g {
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="warehouse-container">

        <!-- Contenido principal -->
        <div class="warehouse-content">
            <!-- Contenedor principal (mapa + controles) -->
            <div class="content-main">
                <!-- Toolbar de controles -->
                <div class="content-toolbar">
                    <!-- Herramientas del mapa -->
                    <div class="toolbar-group">
                        <button id="zoomIn" class="toolbar-btn" title="Zoom aumentar">
                            <i class="fas fa-magnifying-glass-plus"></i>
                        </button>
                        <button id="zoomOut" class="toolbar-btn" title="Zoom disminuir">
                            <i class="fas fa-magnifying-glass-minus"></i>
                        </button>
                        <button id="reset" class="toolbar-btn" title="Centrar vista">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    <div class="search-box" style="margin-left: auto;">
                        <input type="text" id="shelfSearch" placeholder="Buscar estante..." />
                    </div>
                </div>

                <!-- Mapa -->
                <div class="map-container">
                <div class="stage">
                    <svg id="svg" type="image/svg+xml" viewBox="0 0 1600 1100" width="1600" height="1100">
                        <defs>
                            <pattern id="smallGrid" width="50" height="50" patternUnits="userSpaceOnUse">
                                <path d="M 50 0 L 0 0 0 50" class="grid" fill="none" />
                            </pattern>
                            <pattern id="grid" width="250" height="250" patternUnits="userSpaceOnUse">
                                <rect width="250" height="250" fill="url(#smallGrid)" />
                                <path d="M 250 0 L 0 0 0 250" class="grid" fill="none" stroke-width="2" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                        <g id="world">
                            <g class="loading">
                                <circle class="spinner" cx="800" cy="550" r="20" style="fill: none;"></circle>
                            </g>
                            <text x="50%" y="60%" text-anchor="middle" class="loading" style="font-size: 1.2rem;">Cargando almacén...</text>
                        </g>
                    </svg>
                </div>

                </div>
            </div>

            <!-- Panel de información -->
            <div class="info-panel">
                <!-- Selector de pisos -->
                <div class="info-panel-section floor-selector-panel">
                    <div class="info-panel-title">
                        <i class="fas fa-layer-group"></i>
                        Pisos
                    </div>
                    <div class="floor-selector-panel-content">
                        @foreach($floors as $floor)
                            <button id="f{{ $floor->id }}"
                                    class="floor-btn-panel @if($loop->first) active @endif"
                                    data-floor-id="{{ $floor->id }}"
                                    title="{{ $floor->description ?? $floor->name }}">
                                {{ $floor->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="info-panel-section">
                    <div class="info-panel-title">
                        <i class="fas fa-circle-info"></i>
                        Estados de ocupación
                    </div>
                    <div>
                        <div class="legend-item">
                            <div class="legend-color empty"></div>
                            <span>Vacío</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color available"></div>
                            <span>Disponible</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color partial"></div>
                            <span>Parcial</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color occupied"></div>
                            <span>Lleno</span>
                        </div>
                    </div>
                </div>

                <div class="info-panel-section">
                    <div class="info-panel-title">
                        <i class="fas fa-chart-pie"></i>
                        Estadísticas
                    </div>
                    <div id="statsContainer">
                        <div class="legend-item">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Estante (Bootstrap compatible) -->
    <div id="shelfModal" class="modal modal-shelf" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered custom-modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="shelfModalTitle">
                    <i class="fas fa-box"></i>
                    <span id="shelfName">Estante</span>
                </h2>
                <button type="button" class="modal-close-btn" data-close aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Detalles de la ubicación -->
                <div id="locationDetailsContainer">
                    <div id="shelfDetails" class="shelf-details" style="display: none;">
                        <div class="shelf-details-grid" id="detailsGrid"></div>
                    </div>

                    <!-- Contenedor de caras -->
                    <div id="facesContainer" class="faces-container"></div>
                </div>

                <!-- Detalles de la sección seleccionada -->
                <div id="sectionDetailsContainer" style="display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #e5e7eb;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #000;">
                        <i class="fas fa-info-circle"></i>
                        Detalles de la Sección
                    </h3>
                    <div id="sectionDetails" class="shelf-details">
                        <div class="shelf-details-grid" id="sectionDetailsGrid"></div>
                    </div>
                    <div id="sectionSlotsContainer" style="margin-top: 1rem;"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn" data-close>
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" data-close>
                    <i class="fas fa-check"></i>
                    Aceptar
                </button>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        'use strict';

        // ======= Parámetros base =======
        const WAREHOUSE = { width_m: 42.23, height_m: 30.26 };
        let SCALE = 30; // Ahora dinámico
        const MARGIN_M = 0.5;
        let VB_PAD = 2 * SCALE;

        // ======= SISTEMA DE ESCALADO DINÁMICO =======
        const SCALE_SYSTEM = {
            MIN_SCALE: 15,
            MAX_SCALE: 60,
            currentScale: 30,

            calculateDynamicScale() {
                const container = document.querySelector('.map-container');
                if (!container) return this.currentScale;

                const width = container.clientWidth;
                const height = container.clientHeight;

                // Calcular escala basada en disponibilidad
                const scaleWidth = (width - 100) / WAREHOUSE.width_m;
                const scaleHeight = (height - 100) / WAREHOUSE.height_m;

                // Usar la escala más pequeña para que quepa todo
                const calculatedScale = Math.min(scaleWidth, scaleHeight);

                // Restringir al rango válido
                return Math.max(this.MIN_SCALE, Math.min(this.MAX_SCALE, calculatedScale));
            },

            applyScale(newScale) {
                SCALE = newScale;
                VB_PAD = 2 * SCALE;
                this.currentScale = newScale;
            },

            setupResponsiveScaling(preferredScale = null) {
                const initialScale = preferredScale
                    ? Math.max(this.MIN_SCALE, Math.min(this.MAX_SCALE, preferredScale))
                    : this.calculateDynamicScale();
                this.applyScale(initialScale);

                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        const newScale = this.calculateDynamicScale();
                        if (Math.abs(newScale - this.currentScale) > 1) {
                            this.applyScale(newScale);
                            // Redibujar el almacén con la nueva escala
                            if (layoutSpec.length > 0) {
                                world.innerHTML = '';
                                drawFloorGroup(currentFloor);
                            }
                        }
                    }, 250); // Debounce
                });
            }
        };

        // ======= SISTEMA DE DISTRIBUCIÓN Y DETECCIÓN DE SOLAPAMIENTOS =======
        const VECTOR_DISTRIBUTION = {
            STAND_WIDTH: 2.5, // metros
            STAND_HEIGHT: 2.5,
            SPACING: 0.3,
            MARGIN: 20, // pixels

            calculateOptimalPositions(locationsData, scale) {
                const positions = [];

                locationsData.forEach(stand => {
                    const gridX = Math.floor(stand.position_x / (this.STAND_WIDTH + this.SPACING));
                    const gridY = Math.floor(stand.position_y / (this.STAND_HEIGHT + this.SPACING));

                    positions.push({
                        id: stand.id,
                        x: gridX * (this.STAND_WIDTH + this.SPACING) * scale,
                        y: gridY * (this.STAND_HEIGHT + this.SPACING) * scale,
                        faces: stand.faces || 2,
                        type: stand.type || 'row'
                    });
                });

                return positions;
            },

            resolveOverlaps(positions) {
                for (let i = 0; i < positions.length; i++) {
                    for (let j = i + 1; j < positions.length; j++) {
                        const p1 = positions[i];
                        const p2 = positions[j];

                        const dist = Math.sqrt(
                            Math.pow(p2.x - p1.x, 2) +
                            Math.pow(p2.y - p1.y, 2)
                        );

                        // Si está muy cerca, desplazar
                        if (dist < this.MARGIN + 40) {
                            const angle = Math.atan2(p2.y - p1.y, p2.x - p1.x);
                            p2.x += Math.cos(angle) * (this.MARGIN - dist);
                            p2.y += Math.sin(angle) * (this.MARGIN - dist);
                        }
                    }
                }

                return positions;
            }
        };

        // ======= FUNCIONES PARA CREAR VECTORES SVG =======
        const SVG_VECTORS = {
            // Convertir metros a píxeles SVG basado en la escala
            metersToSVGUnits(meters, scale = SCALE) {
                return meters * scale;
            },

            // Vector de una cara (WALL, ISLAND)
            createSingleFaceVector(standId, section, x, y) {
                const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                g.setAttribute('class', 'svg-shelf-vector');
                g.setAttribute('data-shelf-id', section.id);
                g.setAttribute('data-location-uid', section.uid || section.id);
                g.setAttribute('data-faces', '1');
                g.style.cursor = 'pointer';

                // Agregar tooltip
                const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                title.textContent = section.nameTemplate || section.id;
                g.appendChild(title);

                // Usar dimensiones dinámicas del estilo (en metros convertidas a SVG units)
                const width = this.metersToSVGUnits(section.shelf?.w_m || 1.0);
                const height = this.metersToSVGUnits(section.shelf?.h_m || 1.0);

                // Cuerpo principal
                const body = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                body.setAttribute('x', x);
                body.setAttribute('y', y);
                body.setAttribute('width', width);
                body.setAttribute('height', height);
                body.setAttribute('rx', '2');
                body.setAttribute('fill', section.color ? this.getGradientFill(section.color, 'single') : 'url(#grad-single)');
                body.setAttribute('stroke', '#60a5fa');
                body.setAttribute('stroke-width', '0.5');
                body.setAttribute('opacity', '0.9');

                // Marco de la cara
                const faceFrame = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                faceFrame.setAttribute('x', x + 1);
                faceFrame.setAttribute('y', y + 1);
                faceFrame.setAttribute('width', width - 2);
                faceFrame.setAttribute('height', height - 2);
                faceFrame.setAttribute('rx', '1');
                faceFrame.setAttribute('fill', 'none');
                faceFrame.setAttribute('stroke', '#a78bfa');
                faceFrame.setAttribute('stroke-width', '0.3');
                faceFrame.setAttribute('opacity', '0.6');

                // Divisiones de slots
                const slotCount = 4;
                const slotWidth = (width - 2) / slotCount;
                for (let i = 1; i < slotCount; i++) {
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', x + 1 + i * slotWidth);
                    line.setAttribute('y1', y + 1);
                    line.setAttribute('x2', x + 1 + i * slotWidth);
                    line.setAttribute('y2', y + height - 1);
                    line.setAttribute('stroke', '#60a5fa');
                    line.setAttribute('stroke-width', '0.2');
                    line.setAttribute('opacity', '0.4');
                    g.appendChild(line);
                }

                // Indicador de una cara
                const indicator = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                indicator.setAttribute('cx', x + width / 2);
                indicator.setAttribute('cy', y + height + 2);
                indicator.setAttribute('r', '1.5');
                indicator.setAttribute('fill', '#60a5fa');
                indicator.setAttribute('opacity', '0.8');

                g.appendChild(body);
                g.appendChild(faceFrame);
                g.appendChild(indicator);

                return g;
            },

            // Vector de dos caras (ROW, COLUMNS)
            createDualFaceVector(standId, section, x, y) {
                const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                g.setAttribute('class', 'svg-shelf-vector');
                g.setAttribute('data-shelf-id', section.id);
                g.setAttribute('data-location-uid', section.uid || section.id);
                g.setAttribute('data-faces', '2');
                g.style.cursor = 'pointer';

                // Agregar tooltip
                const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                title.textContent = section.nameTemplate || section.id;
                g.appendChild(title);

                // Usar dimensiones dinámicas del estilo (en metros convertidas a SVG units)
                const width = this.metersToSVGUnits(section.shelf?.w_m || 2.0);
                const height = this.metersToSVGUnits(section.shelf?.h_m || 1.5);
                const centerX = x + width / 2;

                // Cuerpo central oscuro
                const body = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                body.setAttribute('x', x);
                body.setAttribute('y', y);
                body.setAttribute('width', width);
                body.setAttribute('height', height);
                body.setAttribute('rx', '2');
                body.setAttribute('fill', '#0f172a');
                body.setAttribute('stroke', '#374151');
                body.setAttribute('stroke-width', '0.5');
                body.setAttribute('opacity', '0.95');

                // Cara izquierda (MORADA/PÚRPURA)
                const leftFace = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                leftFace.setAttribute('x', x + 1);
                leftFace.setAttribute('y', y + 1);
                leftFace.setAttribute('width', width / 2 - 1.5);
                leftFace.setAttribute('height', height - 2);
                leftFace.setAttribute('rx', '1');
                leftFace.setAttribute('fill', 'url(#grad-left)');
                leftFace.setAttribute('stroke', '#a78bfa');
                leftFace.setAttribute('stroke-width', '0.3');
                leftFace.setAttribute('opacity', '0.85');

                // Cara derecha (CIAN/CYAN)
                const rightFace = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rightFace.setAttribute('x', x + width / 2 + 0.5);
                rightFace.setAttribute('y', y + 1);
                rightFace.setAttribute('width', width / 2 - 1.5);
                rightFace.setAttribute('height', height - 2);
                rightFace.setAttribute('rx', '1');
                rightFace.setAttribute('fill', 'url(#grad-right)');
                rightFace.setAttribute('stroke', '#06b6d4');
                rightFace.setAttribute('stroke-width', '0.3');
                rightFace.setAttribute('opacity', '0.85');

                // Divisor central
                const divider = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                divider.setAttribute('x1', centerX);
                divider.setAttribute('y1', y + 1);
                divider.setAttribute('x2', centerX);
                divider.setAttribute('y2', y + height - 1);
                divider.setAttribute('stroke', '#374151');
                divider.setAttribute('stroke-width', '0.3');
                divider.setAttribute('opacity', '0.5');

                // Divisiones de slots izquierda
                const slotCount = 3;
                const slotWidthL = (width / 2 - 2) / slotCount;
                for (let i = 1; i < slotCount; i++) {
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', x + 1 + i * slotWidthL);
                    line.setAttribute('y1', y + 1);
                    line.setAttribute('x2', x + 1 + i * slotWidthL);
                    line.setAttribute('y2', y + height - 1);
                    line.setAttribute('stroke', '#a78bfa');
                    line.setAttribute('stroke-width', '0.2');
                    line.setAttribute('opacity', '0.4');
                    g.appendChild(line);
                }

                // Divisiones de slots derecha
                for (let i = 1; i < slotCount; i++) {
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', centerX + i * slotWidthL);
                    line.setAttribute('y1', y + 1);
                    line.setAttribute('x2', centerX + i * slotWidthL);
                    line.setAttribute('y2', y + height - 1);
                    line.setAttribute('stroke', '#06b6d4');
                    line.setAttribute('stroke-width', '0.2');
                    line.setAttribute('opacity', '0.4');
                    g.appendChild(line);
                }

                // Indicador de dos caras (ojo)
                const indicator = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                indicator.setAttribute('cx', centerX);
                indicator.setAttribute('cy', y + height + 2);
                indicator.setAttribute('r', '1.2');
                indicator.setAttribute('fill', '#60a5fa');
                indicator.setAttribute('opacity', '0.8');

                g.appendChild(body);
                g.appendChild(leftFace);
                g.appendChild(rightFace);
                g.appendChild(divider);
                g.appendChild(indicator);

                return g;
            },

            // Vector de cuatro caras (ISLAND - Isla cuadrada 360°)
            createIslandVector(standId, section, x, y) {
                const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                g.setAttribute('class', 'svg-shelf-vector');
                g.setAttribute('data-shelf-id', section.id);
                g.setAttribute('data-location-uid', section.uid || section.id);
                g.setAttribute('data-faces', '4');
                g.style.cursor = 'pointer';

                // Agregar tooltip
                const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                title.textContent = section.nameTemplate || section.id;
                g.appendChild(title);

                // Usar dimensiones dinámicas del estilo (en metros convertidas a SVG units)
                const width = this.metersToSVGUnits(section.shelf?.w_m || 3.0);
                const height = this.metersToSVGUnits(section.shelf?.h_m || 3.0);
                // Cuerpo central con color dinámico
                const body = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                body.setAttribute('x', x);
                body.setAttribute('y', y);
                body.setAttribute('width', width);
                body.setAttribute('height', height);
                body.setAttribute('rx', '3');
                body.setAttribute('fill', section.color ? this.getGradientFill(section.color, 'single') : 'url(#grad-single)');
                body.setAttribute('stroke', '#60a5fa');
                body.setAttribute('stroke-width', '0.5');
                body.setAttribute('opacity', '0.9');

                // Cara frontal (abajo - front) - Verde
                const frontFace = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                frontFace.setAttribute('x', x + 1);
                frontFace.setAttribute('y', y + height / 2 + 0.5);
                frontFace.setAttribute('width', width - 2);
                frontFace.setAttribute('height', height / 2 - 1.5);
                frontFace.setAttribute('rx', '1');
                frontFace.setAttribute('fill', 'url(#grad-front-island)');
                frontFace.setAttribute('stroke', '#10b981');
                frontFace.setAttribute('stroke-width', '0.3');
                frontFace.setAttribute('opacity', '0.85');

                // Cara posterior (arriba - back) - Ámbar
                const backFace = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                backFace.setAttribute('x', x + 1);
                backFace.setAttribute('y', y + 1);
                backFace.setAttribute('width', width - 2);
                backFace.setAttribute('height', height / 2 - 1.5);
                backFace.setAttribute('rx', '1');
                backFace.setAttribute('fill', 'url(#grad-back-island)');
                backFace.setAttribute('stroke', '#f59e0b');
                backFace.setAttribute('stroke-width', '0.3');
                backFace.setAttribute('opacity', '0.85');

                // Cara izquierda (left) - Púrpura
                const leftFace = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                leftFace.setAttribute('x', x + 1);
                leftFace.setAttribute('y', y + 1);
                leftFace.setAttribute('width', width / 2 - 1.5);
                leftFace.setAttribute('height', height - 2);
                leftFace.setAttribute('rx', '1');
                leftFace.setAttribute('fill', 'url(#grad-left)');
                leftFace.setAttribute('stroke', '#a78bfa');
                leftFace.setAttribute('stroke-width', '0.3');
                leftFace.setAttribute('opacity', '0.75');

                // Cara derecha (right) - Cian
                const rightFace = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rightFace.setAttribute('x', x + width / 2 + 0.5);
                rightFace.setAttribute('y', y + 1);
                rightFace.setAttribute('width', width / 2 - 1.5);
                rightFace.setAttribute('height', height - 2);
                rightFace.setAttribute('rx', '1');
                rightFace.setAttribute('fill', 'url(#grad-right)');
                rightFace.setAttribute('stroke', '#06b6d4');
                rightFace.setAttribute('stroke-width', '0.3');
                rightFace.setAttribute('opacity', '0.75');

                // Divisor horizontal
                const dividerH = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                dividerH.setAttribute('x1', x + 1);
                dividerH.setAttribute('y1', y + height / 2);
                dividerH.setAttribute('x2', x + width - 1);
                dividerH.setAttribute('y2', y + height / 2);
                dividerH.setAttribute('stroke', '#374151');
                dividerH.setAttribute('stroke-width', '0.4');
                dividerH.setAttribute('opacity', '0.6');

                // Divisor vertical
                const dividerV = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                dividerV.setAttribute('x1', x + width / 2);
                dividerV.setAttribute('y1', y + 1);
                dividerV.setAttribute('x2', x + width / 2);
                dividerV.setAttribute('y2', y + height - 1);
                dividerV.setAttribute('stroke', '#374151');
                dividerV.setAttribute('stroke-width', '0.4');
                dividerV.setAttribute('opacity', '0.6');

                // Indicador de 4 caras (cuatro puntos en las esquinas)
                const indicators = [
                    { cx: x + 5, cy: y + 5 },
                    { cx: x + width - 5, cy: y + 5 },
                    { cx: x + 5, cy: y + height - 5 },
                    { cx: x + width - 5, cy: y + height - 5 }
                ];

                indicators.forEach(pos => {
                    const indicator = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    indicator.setAttribute('cx', pos.cx);
                    indicator.setAttribute('cy', pos.cy);
                    indicator.setAttribute('r', '1');
                    indicator.setAttribute('fill', '#60a5fa');
                    indicator.setAttribute('opacity', '0.8');
                    g.appendChild(indicator);
                });

                g.appendChild(body);
                g.appendChild(leftFace);
                g.appendChild(rightFace);
                g.appendChild(backFace);
                g.appendChild(frontFace);
                g.appendChild(dividerH);
                g.appendChild(dividerV);

                return g;
            },

            getGradientFill(colorClass, type) {
                const colorMap = {
                    'shelf--rojo': { single: 'url(#grad-red)', dual: '#ef4444' },
                    'shelf--azul': { single: 'url(#grad-blue)', dual: '#3b82f6' },
                    'shelf--verde': { single: 'url(#grad-green)', dual: '#10b981' },
                    'shelf--ambar': { single: 'url(#grad-amber)', dual: '#f59e0b' },
                    'shelf--morado': { single: 'url(#grad-purple)', dual: '#a78bfa' },
                    'shelf--gris': { single: 'url(#grad-gray)', dual: '#9ca3af' }
                };
                return colorMap[colorClass]?.[type] || (type === 'single' ? 'url(#grad-single)' : '#3b82f6');
            },

            addGradientDefinitions(svgElement) {
                const defs = svgElement.querySelector('defs') || document.createElementNS('http://www.w3.org/2000/svg', 'defs');

                // Gradientes para una cara
                const gradients = [
                    { id: 'grad-single', colors: ['#3b82f6', '#1e40af'] },
                    { id: 'grad-red', colors: ['#ef4444', '#dc2626'] },
                    { id: 'grad-blue', colors: ['#3b82f6', '#1e40af'] },
                    { id: 'grad-green', colors: ['#10b981', '#059669'] },
                    { id: 'grad-amber', colors: ['#f59e0b', '#d97706'] },
                    { id: 'grad-purple', colors: ['#a78bfa', '#8b5cf6'] },
                    { id: 'grad-gray', colors: ['#9ca3af', '#6b7280'] },
                    // Gradientes para dos caras
                    { id: 'grad-left', colors: ['#8b5cf6', '#6d28d9'] },
                    { id: 'grad-right', colors: ['#06b6d4', '#0891b2'] },
                    // Gradientes para islas (4 caras)
                    { id: 'grad-front-island', colors: ['#10b981', '#059669'] },
                    { id: 'grad-back-island', colors: ['#f59e0b', '#d97706'] }
                ];

                gradients.forEach(grad => {
                    if (defs.querySelector(`#${grad.id}`)) return; // Ya existe

                    const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
                    gradient.setAttribute('id', grad.id);
                    gradient.setAttribute('x1', '0%');
                    gradient.setAttribute('y1', '0%');
                    gradient.setAttribute('x2', '100%');
                    gradient.setAttribute('y2', '100%');

                    grad.colors.forEach((color, idx) => {
                        const stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
                        stop.setAttribute('offset', `${(idx / (grad.colors.length - 1)) * 100}%`);
                        stop.setAttribute('stop-color', color);
                        stop.setAttribute('stop-opacity', '1');
                        gradient.appendChild(stop);
                    });

                    defs.appendChild(gradient);
                });

                if (!svgElement.querySelector('defs')) {
                    svgElement.insertBefore(defs, svgElement.firstChild);
                }
            }
        };

        // ======= SISTEMA DE DETECCIÓN Y PREVENCIÓN DE SOLAPAMIENTOS =======
        const OVERLAP_PREVENTION = {
            // Calcular bounding box de un elemento
            calculateBounds(x, y, width, height) {
                return {
                    x1: x,
                    y1: y,
                    x2: x + width,
                    y2: y + height
                };
            },

            // Detectar si dos rectángulos se solapan
            doRectanglesOverlap(rect1, rect2, minSpacing = 2) {
                return !(rect2.x1 >= rect1.x2 + minSpacing ||
                         rect2.x2 + minSpacing <= rect1.x1 ||
                         rect2.y1 >= rect1.y2 + minSpacing ||
                         rect2.y2 + minSpacing <= rect1.y1);
            },

            // Resolver solapamientos ajustando posiciones
            resolveOverlaps(elementData) {
                const resolved = [...elementData];
                const minSpacing = 3; // píxeles mínimos entre elementos
                let hasChanges = true;
                let iterations = 0;
                const maxIterations = 10; // evitar bucle infinito

                // Iterar hasta que no haya más solapamientos
                while (hasChanges && iterations < maxIterations) {
                    hasChanges = false;
                    iterations++;

                    // Verificar TODOS los pares de elementos
                    for (let i = 0; i < resolved.length; i++) {
                        for (let j = i + 1; j < resolved.length; j++) {
                            const elem1 = resolved[i];
                            const elem2 = resolved[j];

                            const bounds1 = this.calculateBounds(elem1.x, elem1.y, elem1.width, elem1.height);
                            const bounds2 = this.calculateBounds(elem2.x, elem2.y, elem2.width, elem2.height);

                            // Si se solapan, desplazar elem2 a la derecha
                            if (this.doRectanglesOverlap(bounds1, bounds2, minSpacing)) {
                                elem2.x = bounds1.x2 + minSpacing;
                                hasChanges = true;
                            }
                        }
                    }
                }

                return resolved;
            }
        };

        // ======= Variables globales =======
        const svg = document.getElementById('svg');
        const world = document.getElementById('world');
        const modal = document.getElementById('shelfModal');
        const facesContainer = document.getElementById('facesContainer');
        const shelfDetailsDiv = document.getElementById('shelfDetails');
        const detailsGrid = document.getElementById('detailsGrid');

        let currentFloor = {{ $floors->first()->id ?? 1 }};
        let warehouseConfig = null;
        let layoutSpec = [];
        let allShelves = [];
        const SHELF_META = Object.create(null);

        // Mapeo de floor_id a floor_uid para construir URLs
        const floorIdToUid = {
            @foreach($floors as $floor)
                {{ $floor->id }}: '{{ $floor->uid }}',
            @endforeach
        };

        const warehouseUid = '{{ $warehouse_uid }}';

        // ======= Funciones Modal =======
        function showModal(show) {
            modal.classList.toggle('show', show);
            modal.setAttribute('aria-hidden', show ? 'false' : 'true');
        }

        function setupModalControls() {
            modal.querySelectorAll('[data-close]').forEach(el => {
                el.addEventListener('click', () => showModal(false));
            });
            // Cerrar modal al hacer clic en el backdrop
            modal.addEventListener('click', (e) => {
                if (e.target === modal) showModal(false);
            });
        }

        // ======= Inicialización =======
        async function init() {
            try {
                // Carga configuración y ajusta dimensiones/escala base
                const configRes = await axios.get('{{ route("manager.warehouse.api.config", ["warehouse_uid" => $warehouse_uid]) }}');
                warehouseConfig = configRes.data;

                if (warehouseConfig?.warehouse) {
                    WAREHOUSE.width_m = warehouseConfig.warehouse.width_m ?? WAREHOUSE.width_m;
                    WAREHOUSE.height_m = warehouseConfig.warehouse.height_m ?? WAREHOUSE.height_m;
                }

                // Configurar escalado dinámico respetando escala almacenada
                const preferredScale = Number(warehouseConfig?.scale);
                SCALE_SYSTEM.setupResponsiveScaling(Number.isFinite(preferredScale) ? preferredScale : null);

                // Agregar definiciones de gradientes
                SVG_VECTORS.addGradientDefinitions(svg);

                // Carga layout
                await loadLayout();

                // Configura controles
                setupControls();
                setupModalControls();
                setupSearch();
                updateStats();
            } catch (error) {
                console.error('Error loading warehouse:', error);
                world.innerHTML = '<text x="50%" y="50%" text-anchor="middle" fill="#ef4444">Error al cargar almacén</text>';
            }
        }

        async function loadLayout() {
            const layoutRes = await axios.get('{{ route("manager.warehouse.api.layout", ["warehouse_uid" => $warehouse_uid]) }}', {
                params: { floor_id: currentFloor }
            });
            layoutSpec = layoutRes.data.layoutSpec || [];
            allShelves = layoutSpec;

            // Limpia y redibuja
            world.innerHTML = '';
            drawFloorGroup(currentFloor);
        }

        // ======= Búsqueda =======
        function setupSearch() {
            const searchInput = document.getElementById('shelfSearch');
            if (!searchInput) return;

            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                const shelves = document.querySelectorAll('.svg-shelf-vector');

                shelves.forEach(shelf => {
                    const shelfId = shelf.getAttribute('data-shelf-id');
                    if (query === '' || shelfId.toLowerCase().includes(query)) {
                        shelf.style.opacity = '1';
                        shelf.style.pointerEvents = 'auto';
                        shelf.style.filter = 'drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3))';
                    } else {
                        shelf.style.opacity = '0.25';
                        shelf.style.pointerEvents = 'none';
                        shelf.style.filter = 'drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) grayscale(60%)';
                    }
                });
            });
        }

        // ======= Estadísticas =======
        function updateStats() {
            const occupied = layoutSpec.filter(s => {
                // Detectar si está ocupado por el color
                return s.color && (s.color.includes('rojo') || s.color.includes('ambar') || s.color.includes('verde'));
            }).length;

            const total = layoutSpec.length;
            const occupancyPct = total > 0 ? Math.round((occupied / total) * 100) : 0;

            const statsHtml = `
                <div class="legend-item">
                    <i class="fas fa-box"></i>
                    <span><strong>${total}</strong> Estantes</span>
                </div>
                <div class="legend-item">
                    <i class="fas fa-check" style="color: #10b981;"></i>
                    <span><strong>${occupied}</strong> Ocupados</span>
                </div>
                <div class="legend-item">
                    <i class="fas fa-chart-pie"></i>
                    <span><strong>${occupancyPct}%</strong> Ocupación</span>
                </div>
            `;

            document.getElementById('statsContainer').innerHTML = statsHtml;
        }

        function setupControls() {
            // Floor buttons (both toolbar and panel)
            document.querySelectorAll('button[data-floor-id]').forEach(btn => {
                btn.addEventListener('click', async function() {
                    currentFloor = parseInt(this.dataset.floorId);
                    // Remove active class from all floor buttons
                    document.querySelectorAll('.floor-btn, .floor-btn-panel').forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    await loadLayout();
                    updateStats();
                    // Limpiar búsqueda
                    const searchInput = document.getElementById('shelfSearch');
                    if (searchInput) searchInput.value = '';
                });
            });

            // Zoom/pan con soporte para mobile y desktop
            let view = { x: 0, y: 0, scale: 1 };
            const MIN_SCALE = 0.3;
            const MAX_SCALE = 5;

            function setTransform() {
                world.setAttribute('transform', `translate(${view.x} ${view.y}) scale(${view.scale})`);
            }

            function svgPointFromClient(e) {
                const rect = svg.getBoundingClientRect();
                return {
                    x: (e.clientX - rect.left - view.x) / view.scale,
                    y: (e.clientY - rect.top - view.y) / view.scale
                };
            }

            function constrainScale(scale) {
                return Math.max(MIN_SCALE, Math.min(MAX_SCALE, scale));
            }

            // Zoom con rueda del mouse (desktop)
            svg.addEventListener('wheel', (e) => {
                e.preventDefault();
                const f = (e.deltaY < 0) ? 1.1 : 0.9;
                const p = svgPointFromClient(e);
                view.x = p.x * (1 - f) * view.scale + view.x;
                view.y = p.y * (1 - f) * view.scale + view.y;
                view.scale = constrainScale(view.scale * f);
                setTransform();
            }, { passive: false });

            // Panning con mouse (desktop)
            let panning = false, start = {}, startView = {};
            svg.addEventListener('pointerdown', e => {
                // 🔹 NO iniciar panning si hago clic sobre un estante vectorial
                if (!e.target.closest('.warehouse-pan')) return;

                panning = true;
                start = { x: e.clientX, y: e.clientY };
                startView = { ...view };
                svg.setPointerCapture(e.pointerId);
            });
            svg.addEventListener('pointermove', e => {
                if (!panning) return;
                view.x = startView.x + (e.clientX - start.x);
                view.y = startView.y + (e.clientY - start.y);
                setTransform();
            });
            svg.addEventListener('pointerup', e => {
                panning = false;
                svg.releasePointerCapture(e.pointerId);
            });

            // ===== SOPORTE PARA GESTOS TÁCTILES (MOBILE) =====
            let touches = [];
            let lastDistance = 0;
            let lastCenter = { x: 0, y: 0 };

            function getTouchDistance(touch1, touch2) {
                const dx = touch2.clientX - touch1.clientX;
                const dy = touch2.clientY - touch1.clientY;
                return Math.sqrt(dx * dx + dy * dy);
            }

            function getTouchCenter(touch1, touch2) {
                return {
                    x: (touch1.clientX + touch2.clientX) / 2,
                    y: (touch1.clientY + touch2.clientY) / 2
                };
            }

            svg.addEventListener('touchstart', (e) => {
                touches = Array.from(e.touches);

                if (touches.length === 2) {
                    e.preventDefault();
                    lastDistance = getTouchDistance(touches[0], touches[1]);
                    lastCenter = getTouchCenter(touches[0], touches[1]);
                }
            }, { passive: false });

            svg.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    e.preventDefault();

                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    const distance = getTouchDistance(touch1, touch2);
                    const center = getTouchCenter(touch1, touch2);

                    // Pinch to zoom
                    if (lastDistance > 0) {
                        const scaleFactor = distance / lastDistance;
                        const rect = svg.getBoundingClientRect();

                        // Punto focal relativo al SVG
                        const focusX = (center.x - rect.left - view.x) / view.scale;
                        const focusY = (center.y - rect.top - view.y) / view.scale;

                        const oldScale = view.scale;
                        view.scale = constrainScale(view.scale * scaleFactor);

                        // Ajustar posición para zoom centrado en el punto focal
                        const actualScaleFactor = view.scale / oldScale;
                        view.x = focusX * (1 - actualScaleFactor) * oldScale + view.x;
                        view.y = focusY * (1 - actualScaleFactor) * oldScale + view.y;
                    }

                    // Pan con dos dedos
                    const dx = center.x - lastCenter.x;
                    const dy = center.y - lastCenter.y;
                    view.x += dx;
                    view.y += dy;

                    lastDistance = distance;
                    lastCenter = center;
                    setTransform();
                } else if (e.touches.length === 1 && e.target.closest('.warehouse-pan')) {
                    // Pan con un dedo solo sobre el fondo
                    e.preventDefault();
                    const touch = e.touches[0];

                    if (!lastCenter.x && !lastCenter.y) {
                        lastCenter = { x: touch.clientX, y: touch.clientY };
                        return;
                    }

                    const dx = touch.clientX - lastCenter.x;
                    const dy = touch.clientY - lastCenter.y;
                    view.x += dx;
                    view.y += dy;

                    lastCenter = { x: touch.clientX, y: touch.clientY };
                    setTransform();
                }
            }, { passive: false });

            svg.addEventListener('touchend', (e) => {
                if (e.touches.length < 2) {
                    lastDistance = 0;
                }
                if (e.touches.length === 0) {
                    lastCenter = { x: 0, y: 0 };
                }
            }, { passive: false });

            document.getElementById('zoomIn').onclick = () => {
                view.scale *= 1.15;
                setTransform();
            };
            document.getElementById('zoomOut').onclick = () => {
                view.scale /= 1.15;
                setTransform();
            };
            document.getElementById('reset').onclick = () => {
                view = { x: 0, y: 0, scale: 1 };
                setTransform();
            };
        }

        // ======= Dibujo del mapa =======
        function drawFloorGroup(floorId) {
            const vb = `0 0 ${WAREHOUSE.width_m * SCALE + VB_PAD * 2} ${WAREHOUSE.height_m * SCALE + VB_PAD * 2}`;
            svg.setAttribute('viewBox', vb);

            const wx = VB_PAD, wy = VB_PAD;
            const ww = WAREHOUSE.width_m * SCALE;
            const wh = WAREHOUSE.height_m * SCALE;

            // Contorno almacén
            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', wx);
            rect.setAttribute('y', wy);
            rect.setAttribute('width', ww);
            rect.setAttribute('height', wh);
            rect.setAttribute('class', 'warehouse warehouse-pan'); // 👈
            world.appendChild(rect);

            const rightInnerX = wx + (WAREHOUSE.width_m - MARGIN_M) * SCALE;
            const topInnerY = wy + MARGIN_M * SCALE;

            // Primero, colecta todas las posiciones y dimensiones para detectar solapamientos
            const elementData = [];
            const elementMap = new Map(); // Para mapear índice a section

            layoutSpec.forEach((section, idx) => {
                if (!section.floors.includes(floorId)) return;

                const x = wx + ww - (section.start?.offsetRight_m || 0) * SCALE;
                const y = wy + (section.start?.offsetTop_m || 0) * SCALE;

                // Determinar dimensiones basadas en tipo de vector
                const styleType = section.style_type || section.type;
                const facesArray = section.style_faces || section.faces || [];
                const facesCount = Array.isArray(facesArray) ? facesArray.length : 0;

                let width, height;
                if (styleType === 'wall' || facesCount === 1) {
                    width = SVG_VECTORS.metersToSVGUnits(section.shelf?.w_m || 1.0);
                    height = SVG_VECTORS.metersToSVGUnits(section.shelf?.h_m || 1.0);
                } else if (styleType === 'row' || facesCount === 2) {
                    width = SVG_VECTORS.metersToSVGUnits(section.shelf?.w_m || 2.0);
                    height = SVG_VECTORS.metersToSVGUnits(section.shelf?.h_m || 1.5);
                } else if (styleType === 'island' || facesCount >= 4) {
                    width = SVG_VECTORS.metersToSVGUnits(section.shelf?.w_m || 3.0);
                    height = SVG_VECTORS.metersToSVGUnits(section.shelf?.h_m || 3.0);
                } else {
                    width = SVG_VECTORS.metersToSVGUnits(section.shelf?.w_m || 2.0);
                    height = SVG_VECTORS.metersToSVGUnits(section.shelf?.h_m || 1.5);
                }

                elementData.push({
                    x: x,
                    y: y,
                    width: width,
                    height: height,
                    index: elementData.length
                });

                elementMap.set(elementData.length - 1, { section, styleType, facesCount });
            });

            // Resolver solapamientos
            const resolvedElements = OVERLAP_PREVENTION.resolveOverlaps(elementData);

            // Ahora dibuja con posiciones ajustadas
            resolvedElements.forEach((elem) => {
                const mapData = elementMap.get(elem.index);
                if (!mapData) return;

                const { section, styleType, facesCount } = mapData;
                const x = elem.x;
                const y = elem.y;

                // ID del estante
                const shelfId = `${section.id}__${section.label?.pattern}`;

                let vectorElement;

                // Determinar qué vector crear según el tipo o número de caras
                if (styleType === 'wall' || facesCount === 1) {
                    // Pared: 1 cara (front)
                    vectorElement = SVG_VECTORS.createSingleFaceVector(section.id, section, x, y);
                } else if (styleType === 'row' || facesCount === 2) {
                    // Pasillo: 2 caras (front, back)
                    vectorElement = SVG_VECTORS.createDualFaceVector(section.id, section, x, y);
                } else if (styleType === 'island' || facesCount >= 4) {
                    // Isla: 4 caras (front, back, left, right) - usar vector especial de isla
                    vectorElement = SVG_VECTORS.createIslandVector(section.id, section, x, y);
                } else {
                    // Fallback: detectar por itemLocationsByIndex (legacy)
                    const facesConfig = section.itemLocationsByIndex?.[1] || {};
                    const legacyFaceCount = Object.keys(facesConfig).length;

                    if (legacyFaceCount <= 1) {
                        vectorElement = SVG_VECTORS.createSingleFaceVector(section.id, section, x, y);
                    } else {
                        vectorElement = SVG_VECTORS.createDualFaceVector(section.id, section, x, y);
                    }
                }

                // Evento click - usar el UID directamente desde section
                const locationUid = section.uid || section.id;
                vectorElement.addEventListener('click', (e) => {
                    e.stopPropagation(); // Evitar que el evento se propague al SVG
                    openShelfModal(locationUid);
                });

                world.appendChild(vectorElement);

                // Almacena meta
                if (section.itemLocationsByIndex && section.itemLocationsByIndex[1]) {
                    SHELF_META[shelfId] = {
                        facesConfig: section.itemLocationsByIndex[1]
                    };
                }
            });
        }

        // ======= Modal de Estante =======
        async function openShelfModal(locationUid) {
            // Mostrar estado de carga
            document.getElementById('shelfName').textContent = 'Cargando...';
            facesContainer.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner" style="margin: 0 auto;"></div></div>';
            shelfDetailsDiv.style.display = 'none';
            showModal(true);

            try {
                // Obtener floor_uid del floor actual
                const floorUid = floorIdToUid[currentFloor];

                if (!floorUid) {
                    throw new Error('Floor UID no encontrado');
                }

                // Construir URL del endpoint
                const url = `/manager/warehouse/warehouses/${warehouseUid}/floors/${floorUid}/locations/${locationUid}/api/details`;

                // Hacer llamada AJAX
                const response = await axios.get(url);
                const data = response.data;

                if (!data.success) {
                    throw new Error('Error al cargar los datos de la ubicación');
                }

                // Actualizar nombre
                document.getElementById('shelfName').textContent = data.location.name;

                // Mostrar detalles de la ubicación
                shelfDetailsDiv.style.display = 'block';
                detailsGrid.innerHTML = `
                    <div class="detail-item">
                        <div class="detail-label">Código</div>
                        <div class="detail-value">${data.location.code}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estilo</div>
                        <div class="detail-value">${data.style.name}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Piso</div>
                        <div class="detail-value">${data.floor.name}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Caras</div>
                        <div class="detail-value">${data.style.faces_count}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estado</div>
                        <div class="detail-value">
                            <span style="color: ${data.location.available ? '#10b981' : '#ef4444'};">
                                ${data.location.available ? 'Disponible' : 'No disponible'}
                            </span>
                        </div>
                    </div>
                    ${data.location.notes ? `
                    <div class="detail-item">
                        <div class="detail-label">Notas</div>
                        <div class="detail-value">${data.location.notes}</div>
                    </div>
                    ` : ''}
                `;

                // Renderizar secciones agrupadas por caras
                renderFacesFromAPI(data.sections_by_face, data.style.faces);

            } catch (error) {
                console.error('Error al cargar ubicación:', error);
                facesContainer.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--danger);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Error al cargar los datos de la ubicación</p>
                        <p style="font-size: 0.875rem; color: #000;">${error.message}</p>
                    </div>
                `;
            }
        }

        // ======= Cargar detalles de una sección específica =======
        async function loadSectionDetails(sectionUid) {
            const sectionDetailsContainer = document.getElementById('sectionDetailsContainer');
            const sectionDetailsGrid = document.getElementById('sectionDetailsGrid');
            const sectionSlotsContainer = document.getElementById('sectionSlotsContainer');

            // Mostrar estado de carga
            sectionDetailsContainer.style.display = 'block';
            sectionDetailsGrid.innerHTML = '<div style="text-align: center;"><div class="spinner"></div></div>';
            sectionSlotsContainer.innerHTML = '';

            try {
                // Obtener datos de la ubicación actual para construir la URL
                const floorUid = floorIdToUid[currentFloor];
                const locationUid = document.querySelector('.svg-shelf-vector')?.getAttribute('data-location-uid');

                // Construir URL del endpoint
                const url = `/manager/warehouse/warehouses/${warehouseUid}/floors/${floorUid}/locations/${locationUid}/sections/${sectionUid}/api/details`;

                // Hacer llamada AJAX
                const response = await axios.get(url);
                const data = response.data;

                if (!data.success) {
                    throw new Error('Error al cargar los datos de la sección');
                }

                // Mostrar detalles de la sección
                sectionDetailsGrid.innerHTML = `
                    <div class="detail-item">
                        <div class="detail-label">Código Sección</div>
                        <div class="detail-value">${data.section.code}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Código de Barras</div>
                        <div class="detail-value">${data.section.barcode || 'N/A'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nivel</div>
                        <div class="detail-value">${data.section.level}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Cara</div>
                        <div class="detail-value">${data.section.face}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estado</div>
                        <div class="detail-value">
                            <span style="color: ${data.section.available ? '#10b981' : '#ef4444'};">
                                ${data.section.available ? 'Disponible' : 'No disponible'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Slots Totales</div>
                        <div class="detail-value">${data.slots_count}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Slots Ocupados</div>
                        <div class="detail-value">${data.occupied_slots} / ${data.slots_count}</div>
                    </div>
                    ${data.section.notes ? `
                    <div class="detail-item">
                        <div class="detail-label">Notas</div>
                        <div class="detail-value">${data.section.notes}</div>
                    </div>
                    ` : ''}
                `;

                // Mostrar slots si existen
                if (data.slots && data.slots.length > 0) {
                    let slotsHTML = '<h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #000;">Slots de la Sección</h4>';
                    slotsHTML += '<div class="slots-grid">';

                    data.slots.forEach(slot => {
                        const slotClass = slot.is_occupied ? 'occupied' : 'empty';
                        const slotInfo = slot.product ? `${slot.product.title} (${slot.product.sku})` : 'Vacío';

                        slotsHTML += `
                            <div class="slot-item ${slotClass}" title="${slotInfo}\nCantidad: ${slot.quantity || 0}/${slot.max_quantity || 0}\nPeso: ${slot.weight_current || 0}kg/${slot.weight_max || 0}kg">
                                ${slot.product ? slot.product.sku.substring(0, 3) : '-'}
                            </div>
                        `;
                    });

                    slotsHTML += '</div>';
                    sectionSlotsContainer.innerHTML = slotsHTML;
                } else {
                    sectionSlotsContainer.innerHTML = '<p style="color: #000; text-align: center; padding: 1rem;">No hay slots en esta sección</p>';
                }

            } catch (error) {
                console.error('Error al cargar sección:', error);
                sectionDetailsGrid.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--danger);">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error al cargar los datos de la sección</p>
                        <p style="font-size: 0.875rem;">${error.message}</p>
                    </div>
                `;
            }
        }

        // Renderizar caras desde datos de la API
        function renderFacesFromAPI(sectionsByFace, availableFaces) {
            facesContainer.innerHTML = '';

            if (!sectionsByFace || Object.keys(sectionsByFace).length === 0) {
                facesContainer.innerHTML = '<p style="color: #000; text-align: center; padding: 2rem;">No hay secciones disponibles en esta ubicación</p>';
                return;
            }

            const faceLabels = {
                'left': '<i class="fas fa-arrow-left"></i> Izquierda',
                'right': '<i class="fas fa-arrow-right"></i> Derecha',
                'front': '<i class="fas fa-arrow-down"></i> Frente',
                'back': '<i class="fas fa-arrow-up"></i> Atrás'
            };

            // Iterar sobre cada cara en el orden de availableFaces
            availableFaces.forEach(face => {
                const sections = sectionsByFace[face];

                if (!sections || sections.length === 0) return;

                const faceBlock = document.createElement('div');
                faceBlock.className = 'face-block';

                const header = document.createElement('div');
                header.className = 'face-header';
                header.innerHTML = faceLabels[face] || face.charAt(0).toUpperCase() + face.slice(1);

                const content = document.createElement('div');
                content.className = 'face-content';

                const sectionsContainer = document.createElement('div');
                sectionsContainer.style.cssText = 'display: flex; flex-direction: column; gap: 1rem;';

                // Agrupar secciones por nivel
                const sectionsByLevel = sections.reduce((acc, section) => {
                    if (!acc[section.level]) {
                        acc[section.level] = [];
                    }
                    acc[section.level].push(section);
                    return acc;
                }, {});

                // Renderizar por niveles
                Object.entries(sectionsByLevel).sort(([a], [b]) => a - b).forEach(([level, levelSections]) => {
                    const levelDiv = document.createElement('div');
                    levelDiv.style.cssText = 'margin-bottom: 0.5rem;';

                    const levelLabel = document.createElement('div');
                    levelLabel.style.cssText = 'font-size: 0.75rem; color: #000; margin-bottom: 0.5rem; font-weight: 600;';
                    levelLabel.textContent = `Nivel ${level}`;
                    levelDiv.appendChild(levelLabel);

                    const grid = document.createElement('div');
                    grid.className = 'slots-grid';

                    levelSections.forEach(section => {
                        const sectionEl = document.createElement('div');
                        sectionEl.className = 'slot-item';
                        sectionEl.textContent = section.code || `${section.level}-${section.face}`;
                        sectionEl.setAttribute('data-section-uid', section.uid);

                        // Determinar estado visual según disponibilidad
                        if (section.available) {
                            sectionEl.classList.add('empty');
                        } else {
                            sectionEl.classList.add('occupied');
                        }

                        sectionEl.title = `Sección: ${section.code}\nNivel: ${section.level}\nCara: ${face}\nCódigo de barras: ${section.barcode || 'N/A'}`;

                        // Agregar evento click para mostrar detalles de la sección
                        sectionEl.addEventListener('click', async () => {
                            await loadSectionDetails(section.uid);
                        });

                        grid.appendChild(sectionEl);
                    });

                    levelDiv.appendChild(grid);
                    sectionsContainer.appendChild(levelDiv);
                });

                content.appendChild(sectionsContainer);
                faceBlock.appendChild(header);
                faceBlock.appendChild(content);
                facesContainer.appendChild(faceBlock);
            });
        }

        function renderFaces(section) {
            facesContainer.innerHTML = '';
            const facesConfig = SHELF_META[`${section.id}__${section.label?.pattern}`]?.facesConfig || {};

            if (Object.keys(facesConfig).length === 0) {
                facesContainer.innerHTML = '<p style="color: #000; text-align: center; padding: 2rem;">No hay posiciones disponibles</p>';
                return;
            }

            const faceLabels = {
                'left': '<i class="fas fa-arrow-left"></i> Izquierda',
                'right': '<i class="fas fa-arrow-right"></i> Derecha',
                'front': '<i class="fas fa-arrow-down"></i> Frente',
                'back': '<i class="fas fa-arrow-up"></i> Atrás'
            };

            Object.entries(facesConfig).forEach(([face, slots]) => {
                if (!Array.isArray(slots) || slots.length === 0) return;

                const faceBlock = document.createElement('div');
                faceBlock.className = 'face-block';

                const header = document.createElement('div');
                header.className = 'face-header';
                header.innerHTML = faceLabels[face] || face;

                const content = document.createElement('div');
                content.className = 'face-content';

                const grid = document.createElement('div');
                grid.className = 'slots-grid';

                slots.forEach((slot, idx) => {
                    const slotEl = document.createElement('div');
                    slotEl.className = 'slot-item';
                    slotEl.textContent = idx + 1;

                    // Determinar estado
                    if (slot.color) {
                        if (slot.color.includes('rojo')) slotEl.classList.add('critical');
                        else if (slot.color.includes('ambar')) slotEl.classList.add('warning');
                        else if (slot.color.includes('verde')) slotEl.classList.add('occupied');
                        else slotEl.classList.add('empty');
                    } else {
                        slotEl.classList.add('empty');
                    }

                    slotEl.title = `${face.charAt(0).toUpperCase() + face.slice(1)} - Posición ${idx + 1}`;
                    grid.appendChild(slotEl);
                });

                content.appendChild(grid);
                faceBlock.appendChild(header);
                faceBlock.appendChild(content);
                facesContainer.appendChild(faceBlock);
            });
        }

        function getColorFromClass(colorClass) {
            const colors = {
                'shelf--rojo': '#ef4444',
                'shelf--ambar': '#f59e0b',
                'shelf--verde': '#10b981',
                'shelf--azul': '#3b82f6',
                'shelf--gris': '#9ca3af',
                'shelf--morado': '#a78bfa'
            };
            return colors[colorClass] || '#9ca3af';
        }

        function getStatusLabel(colorClass) {
            const statuses = {
                'shelf--rojo': 'Lleno',
                'shelf--ambar': 'Parcial',
                'shelf--verde': 'Disponible',
                'shelf--azul': 'Vacío',
                'shelf--gris': 'No disponible',
                'shelf--morado': 'Especial'
            };
            return statuses[colorClass] || 'Desconocido';
        }

        // Inicializa la aplicación
        init();
    </script>
</body>
</html>
@endsection
