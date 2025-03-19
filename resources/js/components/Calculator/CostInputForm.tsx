import React from 'react';
import {
  Grid,
  TextField,
  InputAdornment,
  Tooltip,
  IconButton
} from '@mui/material';
import { Help as HelpIcon } from '@mui/icons-material';
import { CalculationParams } from '@/types/calculator';

interface CostInputFormProps {
  costs: CalculationParams;
  onChange: (costs: Partial<CalculationParams>) => void;
}

const CostInputForm: React.FC<CostInputFormProps> = ({ costs, onChange }) => {
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    onChange({ [name]: parseFloat(value) || 0 });
  };

  const tooltips = {
    costPrice: '제품 구매 원가 (단위당)',
    shippingCost: '배송비 (단위당)',
    customsDuty: '관세 및 통관 수수료 (단위당)',
    marketplaceFee: '마켓플레이스 수수료 (%)',
    marketingCost: '마케팅 비용 (단위당)',
    additionalCosts: '기타 추가 비용 (단위당)'
  };

  return (
    <Grid container spacing={2}>
      <Grid item xs={12}>
        <TextField
          fullWidth
          label="구매 원가"
          name="costPrice"
          type="number"
          value={costs.costPrice}
          onChange={handleChange}
          InputProps={{
            startAdornment: <InputAdornment position="start">₩</InputAdornment>,
            endAdornment: (
              <InputAdornment position="end">
                <Tooltip title={tooltips.costPrice}>
                  <IconButton size="small">
                    <HelpIcon />
                  </IconButton>
                </Tooltip>
              </InputAdornment>
            )
          }}
        />
      </Grid>

      <Grid item xs={12}>
        <TextField
          fullWidth
          label="배송비"
          name="shippingCost"
          type="number"
          value={costs.shippingCost}
          onChange={handleChange}
          InputProps={{
            startAdornment: <InputAdornment position="start">₩</InputAdornment>,
            endAdornment: (
              <InputAdornment position="end">
                <Tooltip title={tooltips.shippingCost}>
                  <IconButton size="small">
                    <HelpIcon />
                  </IconButton>
                </Tooltip>
              </InputAdornment>
            )
          }}
        />
      </Grid>

      <Grid item xs={12}>
        <TextField
          fullWidth
          label="관세 및 통관 수수료"
          name="customsDuty"
          type="number"
          value={costs.customsDuty}
          onChange={handleChange}
          InputProps={{
            startAdornment: <InputAdornment position="start">₩</InputAdornment>,
            endAdornment: (
              <InputAdornment position="end">
                <Tooltip title={tooltips.customsDuty}>
                  <IconButton size="small">
                    <HelpIcon />
                  </IconButton>
                </Tooltip>
              </InputAdornment>
            )
          }}
        />
      </Grid>

      <Grid item xs={12}>
        <TextField
          fullWidth
          label="마켓플레이스 수수료"
          name="marketplaceFee"
          type="number"
          value={costs.marketplaceFee}
          onChange={handleChange}
          InputProps={{
            endAdornment: (
              <InputAdornment position="end">
                %
                <Tooltip title={tooltips.marketplaceFee}>
                  <IconButton size="small">
                    <HelpIcon />
                  </IconButton>
                </Tooltip>
              </InputAdornment>
            )
          }}
        />
      </Grid>

      <Grid item xs={12}>
        <TextField
          fullWidth
          label="마케팅 비용"
          name="marketingCost"
          type="number"
          value={costs.marketingCost}
          onChange={handleChange}
          InputProps={{
            startAdornment: <InputAdornment position="start">₩</InputAdornment>,
            endAdornment: (
              <InputAdornment position="end">
                <Tooltip title={tooltips.marketingCost}>
                  <IconButton size="small">
                    <HelpIcon />
                  </IconButton>
                </Tooltip>
              </InputAdornment>
            )
          }}
        />
      </Grid>

      <Grid item xs={12}>
        <TextField
          fullWidth
          label="기타 추가 비용"
          name="additionalCosts"
          type="number"
          value={costs.additionalCosts}
          onChange={handleChange}
          InputProps={{
            startAdornment: <InputAdornment position="start">₩</InputAdornment>,
            endAdornment: (
              <InputAdornment position="end">
                <Tooltip title={tooltips.additionalCosts}>
                  <IconButton size="small">
                    <HelpIcon />
                  </IconButton>
                </Tooltip>
              </InputAdornment>
            )
          }}
        />
      </Grid>
    </Grid>
  );
};

export default CostInputForm; 