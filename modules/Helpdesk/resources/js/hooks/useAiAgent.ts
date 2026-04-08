import { useCallback } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

interface AiAgentConfig {
    id: number;
    name: string;
    description: string;
    personality: string;
    provider: 'openai' | 'anthropic' | 'gemini' | 'local';
    model: string;
    api_key?: string;
    temperature: number;
    max_tokens: number;
    top_p: number;
    frequency_penalty: number;
    presence_penalty: number;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
}

interface ProviderModel {
    id: string;
    name: string;
    description: string;
}

export function useAiAgent(agentId?: number) {
    const queryClient = useQueryClient();

    // Fetch agent backups
    const { data: agent, isLoading: isLoadingAgent } = useQuery({
        queryKey: ['ai-agent', agentId],
        queryFn: () =>
            axios
                .get('/manager/helpdesk/ai-agent/settings')
                .then((r) => r.data),
        staleTime: 5 * 60 * 1000, // 5 minutes
    });

    // Test API connection
    const testConnectionMutation = useMutation({
        mutationFn: (config: {
            provider: string;
            api_key: string;
        }) =>
            axios
                .post('/manager/helpdesk/ai-agent/settings/test-connection', config)
                .then((r) => r.data),
    });

    // Get available models for provider
    const getModelsMutation = useMutation({
        mutationFn: (provider: string) =>
            axios
                .post('/manager/helpdesk/ai-agent/settings/get-models', { provider })
                .then((r) => r.data),
    });

    // Update agent backups
    const updateSettingsMutation = useMutation({
        mutationFn: (config: Partial<AiAgentConfig>) =>
            axios
                .put('/manager/helpdesk/ai-agent/settings', config)
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['ai-agent'] });
        },
    });

    // Get usage statistics
    const { data: statistics, isLoading: isLoadingStats } = useQuery({
        queryKey: ['ai-agent-statistics'],
        queryFn: () =>
            axios
                .get('/manager/helpdesk/ai-agent/settings/statistics')
                .then((r) => r.data),
        refetchInterval: 30 * 1000, // Refetch every 30 seconds
    });

    // Helper to test connection
    const testConnection = useCallback(
        async (provider: string, apiKey: string) => {
            return testConnectionMutation.mutateAsync({
                provider,
                api_key: apiKey,
            });
        },
        [testConnectionMutation]
    );

    // Helper to get models
    const getModels = useCallback(
        async (provider: string) => {
            return getModelsMutation.mutateAsync(provider);
        },
        [getModelsMutation]
    );

    // Helper to update backups
    const updateSettings = useCallback(
        async (config: Partial<AiAgentConfig>) => {
            return updateSettingsMutation.mutateAsync(config);
        },
        [updateSettingsMutation]
    );

    return {
        // State
        agent,
        statistics,
        isLoading: isLoadingAgent || isLoadingStats,
        isLoadingAgent,
        isLoadingStats,

        // Mutations
        isTesting: testConnectionMutation.isPending,
        isGettingModels: getModelsMutation.isPending,
        isUpdating: updateSettingsMutation.isPending,

        // Methods
        testConnection,
        getModels,
        updateSettings,

        // Errors
        errors: {
            connectionError: testConnectionMutation.error,
            modelsError: getModelsMutation.error,
            settingsError: updateSettingsMutation.error,
        },
    };
}
