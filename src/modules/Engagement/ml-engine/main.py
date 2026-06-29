"""
Engagement ML Engine
Simple FastAPI service for predictive analytics.
"""

import os
import pickle
from datetime import datetime
from typing import Any

import numpy as np
import pandas as pd
from fastapi import FastAPI
from pydantic import BaseModel
from sklearn.linear_model import LogisticRegression

app = FastAPI(title="Engagement ML Engine")

MODEL_PATH = "/app/models/conversion_model.pkl"


class ConversionFeatures(BaseModel):
    page_views: int
    time_on_site: float
    cart_events: int
    purchase_events: int
    days_since_first: int
    engagement_score: float


class ConversionResponse(BaseModel):
    probability: float
    tier: str


class NextActionResponse(BaseModel):
    action: str
    confidence: int


def _ensure_model() -> LogisticRegression:
    """Load or create a default logistic regression model."""
    if os.path.exists(MODEL_PATH):
        with open(MODEL_PATH, "rb") as f:
            return pickle.load(f)

    # Default heuristic model
    model = LogisticRegression()
    # Fit with dummy data so predict_proba works
    X = np.array(
        [
            [0, 0, 0, 0, 30, 0],
            [5, 10, 0, 0, 7, 30],
            [10, 30, 2, 0, 3, 60],
            [15, 45, 3, 1, 1, 85],
            [20, 60, 5, 2, 0, 95],
        ]
    )
    y = np.array([0, 0, 1, 1, 1])
    model.fit(X, y)
    os.makedirs(os.path.dirname(MODEL_PATH), exist_ok=True)
    with open(MODEL_PATH, "wb") as f:
        pickle.dump(model, f)
    return model


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok", "time": datetime.utcnow().isoformat()}


@app.post("/predict/conversion", response_model=ConversionResponse)
def predict_conversion(features: ConversionFeatures) -> dict[str, Any]:
    model = _ensure_model()
    X = np.array(
        [
            [
                features.page_views,
                features.time_on_site,
                features.cart_events,
                features.purchase_events,
                features.days_since_first,
                features.engagement_score,
            ]
        ]
    )
    proba = model.predict_proba(X)[0][1]
    probability = float(proba * 100)

    tier = "low"
    if probability >= 70:
        tier = "high"
    elif probability >= 40:
        tier = "medium"

    return {"probability": round(probability, 2), "tier": tier}


@app.post("/predict/next-action", response_model=NextActionResponse)
def predict_next_action(features: ConversionFeatures) -> dict[str, Any]:
    if features.cart_events > 0 and features.purchase_events == 0:
        return {"action": "offer_discount", "confidence": 85}
    if features.page_views > 5:
        return {"action": "show_chat", "confidence": 70}
    if features.engagement_score > 60:
        return {"action": "recommend_product", "confidence": 65}
    return {"action": "wait", "confidence": 50}


@app.post("/train")
def train(data: list[dict[str, Any]]) -> dict[str, Any]:
    """Retrain model with new data."""
    df = pd.DataFrame(data)
    required = [
        "page_views",
        "time_on_site",
        "cart_events",
        "purchase_events",
        "days_since_first",
        "engagement_score",
        "converted",
    ]
    for col in required:
        if col not in df.columns:
            return {"success": False, "error": f"Missing column: {col}"}

    X = df[required[:-1]].values
    y = df["converted"].values

    model = LogisticRegression(max_iter=1000)
    model.fit(X, y)

    os.makedirs(os.path.dirname(MODEL_PATH), exist_ok=True)
    with open(MODEL_PATH, "wb") as f:
        pickle.dump(model, f)

    return {
        "success": True,
        "coefficients": model.coef_.tolist(),
        "intercept": float(model.intercept_[0]),
    }
