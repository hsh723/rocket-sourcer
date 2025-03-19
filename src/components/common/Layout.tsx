import styled from 'styled-components';
import { ReactNode } from 'react';

interface LayoutProps {
  children: ReactNode;
}

const LayoutWrapper = styled.div`
  display: flex;
  min-height: 100vh;
`;

const MainContent = styled.main`
  flex: 1;
  padding: 2rem;
`;

export const Layout = ({ children }: LayoutProps) => (
  <LayoutWrapper>
    <MainContent>{children}</MainContent>
  </LayoutWrapper>
);
