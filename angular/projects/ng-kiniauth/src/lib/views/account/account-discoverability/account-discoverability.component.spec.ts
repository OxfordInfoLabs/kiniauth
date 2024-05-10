import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AccountDiscoverabilityComponent } from './account-discoverability.component';

describe('AccountDiscoverabilityComponent', () => {
  let component: AccountDiscoverabilityComponent;
  let fixture: ComponentFixture<AccountDiscoverabilityComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AccountDiscoverabilityComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AccountDiscoverabilityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
