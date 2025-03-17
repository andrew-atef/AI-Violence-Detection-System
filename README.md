## GitHub Repository: Violence Detection System

**Repository Title:** Violence Detection: Keras, Flask, and React

-----

# Violence Detection System

This repository contains a complete system for violence detection in videos, built using a Keras model for video analysis, a Flask API for serving predictions, and a React frontend for user interaction.

## Overview

The system is designed to analyze short video clips and determine if they contain violent content. It consists of three main components:

1.  **Keras Model:** A deep learning model trained to classify video sequences as either "violence" or "non-violence."
2.  **Flask API:** A RESTful API that exposes the Keras model as an endpoint, allowing users to upload video clips and receive predictions.
3.  **React Frontend:** A user-friendly web interface that allows users to record or upload 5-second video clips and receive real-time violence detection alerts.

## Project Structure

```
violence-detection/
├── keras_model/          # Contains the Keras model and training scripts
│   ├── train.py          # Training script
│   ├── weights.h5        # Pre-trained model weights
├── flask_api/            # Flask API implementation
│   ├── app.py            # Flask application
├── react_frontend/       # React frontend application
│   ├── src/              # React source code
│   │   ├── components/
│   │   │   ├── VideoRecorder.js # React component for video recording and upload
│   │   ├── App.js        # Main React component
│   ├── package.json      # React dependencies
│   ├── public/
├── README.md             # This file
```

## Setup and Installation

### 1\. Keras Model Setup

1.  **Install Dependencies:**
    ```bash
    cd keras_model
    pip install tensorflow keras opencv-python numpy scikit-learn
    ```
2.  **Model Training (Optional):**
      * If you wish to train the model yourself, prepare your video dataset and run `python train.py`.
      * Otherwise, use the provided `weights.h5` file.

### 2\. Flask API Setup

1.  **Install Dependencies:**
    ```bash
    cd ../flask_api
    pip install flask opencv-python tensorflow keras
    ```
2.  **Run the API:**
    ```bash
    python app.py
    ```
    The API will be available at `http://127.0.0.1:5000`. The prediction endpoint is `/predict`.

### 3\. React Frontend Setup

1.  **Install Dependencies:**
    ```bash
    cd ../react_frontend
    npm install
    ```
2.  **Run the Frontend:**
    ```bash
    npm start
    ```
    The frontend will be available at `http://localhost:3000`.

## Usage

1.  **Start the Flask API:** Ensure the API is running as described in the setup instructions.
2.  **Start the React Frontend:** Ensure the frontend is running.
3.  **Record or Upload Video:** In the browser, the React frontend will provide options to record a 5-second video using your webcam or upload a pre-recorded video.
4.  **Receive Prediction:** After the video is processed, the frontend will send the video to the Flask API's `/predict` endpoint.
5.  **View Alert:** The frontend will display an alert indicating whether the video contains violence or not.

## API Endpoint

  * **`/predict` (POST):**
      * Accepts a video file as input.
      * Returns a JSON response: `{"prediction": "violence"}` or `{"prediction": "non-violence"}`.

## Model Details

  * The Keras model is designed to analyze video sequences and extract relevant features for violence detection.
  * The model architecture and training parameters can be found in `keras_model/model.py` and `keras_model/train.py`.

## Future Improvements

  * Improve model accuracy with a larger and more diverse dataset.
  * Implement real-time video stream analysis.
  * Add user authentication and authorization to the API.
  * Add a progress bar in the react frontend to show the processing time.
  * Containerize the application with Docker for easier deployment.
  * Add unit and integration tests.

## Contributing

Contributions are welcome\! Please feel free to submit pull requests or open issues for bug fixes, feature requests, or improvements.
