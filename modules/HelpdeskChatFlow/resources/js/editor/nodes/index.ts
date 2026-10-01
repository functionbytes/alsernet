import { ComponentType } from 'react';
import { NodeConfigProps } from '../types';

import StartConfig from './StartConfig';
import MessageConfig from './MessageConfig';
import SendFileConfig from './SendFileConfig';
import DocumentLinkConfig from './DocumentLinkConfig';
import QuickRepliesConfig from './QuickRepliesConfig';
import CollectInputConfig from './CollectInputConfig';
import IdentifyCustomerConfig from './IdentifyCustomerConfig';
import RequestDocumentsConfig from './RequestDocumentsConfig';
import BranchesConfig from './BranchesConfig';
import BranchItemConfig from './BranchItemConfig';
import ActionConfig from './ActionConfig';
import DelayConfig from './DelayConfig';
import AiResponseConfig from './AiResponseConfig';
import AiAgentConfig from './AiAgentConfig';
import OrderLookupConfig from './OrderLookupConfig';
import HttpRequestConfig from './HttpRequestConfig';
import CsatConfig from './CsatConfig';
import BusinessHoursConfig from './BusinessHoursConfig';
import RichMessageConfig from './RichMessageConfig';
import EndConfig from './EndConfig';
import TransferConfig from './TransferConfig';
import CloseConfig from './CloseConfig';
import GoToStepConfig from './GoToStepConfig';
import AddTagConfig from './AddTagConfig';
import SetAttributeConfig from './SetAttributeConfig';
import CreateTicketConfig from './CreateTicketConfig';
import CallFlowConfig from './CallFlowConfig';
import ReturnConfig from './ReturnConfig';
import AiActionConfig from './AiActionConfig';

// Maps a BackendNode `type` to its configuration panel component, replacing
// the previous giant switch/if-chain. Node types with no entry here render
// no configuration panel.
export const NODE_CONFIG_REGISTRY: Record<string, ComponentType<NodeConfigProps>> = {
    start:              StartConfig,
    message:            MessageConfig,
    send_file:          SendFileConfig,
    document_link:      DocumentLinkConfig,
    quick_replies:      QuickRepliesConfig,
    collect_input:      CollectInputConfig,
    identify_customer:  IdentifyCustomerConfig,
    request_documents:  RequestDocumentsConfig,
    branches:           BranchesConfig,
    branchItem:         BranchItemConfig,
    action:             ActionConfig,
    delay:              DelayConfig,
    ai_response:        AiResponseConfig,
    ai_agent:           AiAgentConfig,
    order_lookup:       OrderLookupConfig,
    http_request:       HttpRequestConfig,
    csat:               CsatConfig,
    business_hours:     BusinessHoursConfig,
    rich_message:       RichMessageConfig,
    end:                EndConfig,
    transfer:           TransferConfig,
    close:              CloseConfig,
    go_to_step:         GoToStepConfig,
    add_tag:            AddTagConfig,
    set_attribute:      SetAttributeConfig,
    create_ticket:      CreateTicketConfig,
    call_flow:          CallFlowConfig,
    return:             ReturnConfig,
    ai_action:          AiActionConfig,
};
