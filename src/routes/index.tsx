import { createBrowserRouter } from 'react-router-dom';
import TrendAnalysis from '../components/modules/TrendAnalysis';
import ProductDiscovery from '../components/modules/ProductDiscovery';
import ProfitAnalysis from '../components/modules/ProfitAnalysis';
import StrategyProposal from '../components/modules/StrategyProposal';
import ProductLifecycle from '../components/modules/ProductLifecycle';
import ActionPlan from '../components/modules/ActionPlan';

export const router = createBrowserRouter([
  {
    path: "/trend-analysis",
    element: <TrendAnalysis />,
  },
  {
    path: "/product-discovery",
    element: <ProductDiscovery />,
  },
  {
    path: "/profit-analysis",
    element: <ProfitAnalysis />,
  },
  {
    path: "/strategy-proposal",
    element: <StrategyProposal />,
  },
  {
    path: "/product-lifecycle",
    element: <ProductLifecycle />,
  },
  {
    path: "/action-plan",
    element: <ActionPlan />,
  }
]);
