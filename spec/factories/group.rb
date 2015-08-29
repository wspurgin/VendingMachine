FactoryGirl.define do
  factory :group do
    name    { FFaker::Company.position.split.first }
  end
end
