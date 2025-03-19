#!/bin/bash

# Create main directory structure
mkdir -p app/Core/Routing
mkdir -p app/Core/Http
mkdir -p app/Core/Exceptions
mkdir -p logs

# Create .gitkeep files to preserve empty directories
touch app/Core/Routing/.gitkeep
touch app/Core/Http/.gitkeep
touch app/Core/Exceptions/.gitkeep
touch logs/.gitkeep
