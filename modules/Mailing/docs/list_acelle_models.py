#!/usr/bin/env python3
"""
Script para listar todos los modelos de Acelle Mail
Extrae nombres de clases desde archivos PHP
"""

import os
import re
from pathlib import Path

# Ruta al directorio de modelos de Acelle
ACELLE_MODELS_DIR = "/Users/functionbytes/Function/Coding/acelle/app/Model"

def extract_class_name(file_path):
    """Extrae el nombre de la clase desde un archivo PHP"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            # Buscar: class NombreClase extends
            match = re.search(r'class\s+(\w+)\s+extends', content)
            if match:
                return match.group(1)
    except Exception as e:
        print(f"Error leyendo {file_path}: {e}")
    return None

def list_all_models():
    """Lista todos los modelos de Acelle"""
    models = []

    if not os.path.exists(ACELLE_MODELS_DIR):
        print(f"ERROR: Directorio no encontrado: {ACELLE_MODELS_DIR}")
        return []

    # Recorrer todos los archivos .php
    for root, dirs, files in os.walk(ACELLE_MODELS_DIR):
        for file in files:
            if file.endswith('.php'):
                file_path = os.path.join(root, file)
                class_name = extract_class_name(file_path)
                if class_name:
                    models.append({
                        'name': class_name,
                        'file': file,
                        'path': file_path.replace(ACELLE_MODELS_DIR + '/', '')
                    })

    return sorted(models, key=lambda x: x['name'])

def main():
    models = list_all_models()

    print(f"Total de modelos encontrados: {len(models)}\n")
    print("LISTA DE MODELOS DE ACELLE:")
    print("=" * 60)

    for i, model in enumerate(models, 1):
        print(f"{i:3d}. {model['name']:<30} ({model['file']})")

    print("\n" + "=" * 60)
    print(f"\nNombres de clases (para verificación):")
    print("---------------------------------------")
    for model in models:
        print(model['name'])

if __name__ == "__main__":
    main()
