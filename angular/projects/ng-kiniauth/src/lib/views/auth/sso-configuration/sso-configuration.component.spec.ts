import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SsoConfigurationComponent } from './sso-configuration.component';

describe('SsoConfigurationComponent', () => {
  let component: SsoConfigurationComponent;
  let fixture: ComponentFixture<SsoConfigurationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SsoConfigurationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SsoConfigurationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
