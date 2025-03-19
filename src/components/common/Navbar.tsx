import styled from 'styled-components';

const Nav = styled.nav`
  background-color: #ffffff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  padding: 1rem 2rem;
`;

const NavList = styled.ul`
  display: flex;
  gap: 2rem;
  list-style: none;
`;

export const Navbar = () => (
  <Nav>
    <NavList>
      <li>홈</li>
      <li>검색</li>
      <li>통계</li>
    </NavList>
  </Nav>
);
