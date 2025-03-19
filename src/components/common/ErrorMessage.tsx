import styled from 'styled-components';

const ErrorContainer = styled.div`
  padding: 1rem;
  background-color: #fff5f5;
  border: 1px solid #fc8181;
  border-radius: 4px;
  color: #c53030;
`;

interface ErrorMessageProps {
  message: string;
}

export const ErrorMessage = ({ message }: ErrorMessageProps) => (
  <ErrorContainer>
    <p>{message}</p>
  </ErrorContainer>
);
