import { api } from '../../services/apiClient';
import { emit } from '../../core/eventBus';
import { logger } from '../../utils/logger';

export interface RecommendedProduct {
  productId: string;
  name: string;
  url: string;
  imageUrl?: string;
  price?: number;
}

interface RecommendationsResponse {
  success: boolean;
  data: {
    products: RecommendedProduct[];
  };
}

let lastFetch = 0;
const MIN_INTERVAL_MS = 60_000;

export async function fetchRecommendations(): Promise<RecommendedProduct[]> {
  const now = Date.now();
  if (now - lastFetch < MIN_INTERVAL_MS) {
    logger.log('recommendations: skipping fetch (rate limited)');
    return [];
  }

  try {
    const res = await api.get<RecommendationsResponse>('sdk/recommendations');
    if (!res.success) return [];

    lastFetch = Date.now();
    const { products } = res.data;

    emit('recommendations:updated', { products });
    logger.log('recommendations fetched:', products.length);

    return products;
  } catch (err) {
    logger.warn('recommendations fetch failed:', err);
    return [];
  }
}
